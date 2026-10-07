package l2f.gameserver.autofarm;

import java.util.concurrent.ScheduledFuture;

import l2f.gameserver.ThreadPoolManager;
import l2f.gameserver.handler.items.IItemHandler;
import l2f.gameserver.model.GameObject;
import l2f.gameserver.model.GameObjectsStorage;
import l2f.gameserver.model.Player;
import l2f.gameserver.model.Skill;
import l2f.gameserver.model.instances.MonsterInstance;
import l2f.gameserver.model.items.ItemInstance;
import l2f.gameserver.templates.item.WeaponTemplate;
import l2f.gameserver.templates.item.WeaponTemplate.WeaponType;

public class AutoFarmTask implements Runnable
{
	private final Player _player;
	private ScheduledFuture<?> _task;

	private int _searchRadius = 30000;
	private int _lastTargetObjectId = 0;
	private double _lastTargetHp = -1.0;
	private long _lastTargetProgress = 0L;
	private static final long TARGET_STUCK_TIMEOUT = 2500L;
	private static final long MOVEMENT_COMMAND_INTERVAL = 500L;

	private int _skill1 = 0;
	private int _skill2 = 0;
	private int _skill3 = 0;
	private int _currentSkill = 1;

	private ItemInstance _lootTarget;
	private int _lastLootObjectId = 0;
	private long _lastLootAttempt = 0;
	private long _lastPotionUse = 0;

	private long _lastMoveCommand = 0L;

	private long _attackStateSince = 0L;
	private static final long ATTACK_STUCK_TIMEOUT = 3500L;
	private static final long POTION_INTERVAL = 3000L;

	public AutoFarmTask(Player player)
	{
		_player = player;
	}

	public void start()
	{
		if (_task != null)
			return;

		_lastTargetObjectId = 0;
		_lastTargetHp = -1.0;
		_lastTargetProgress = System.currentTimeMillis();
		_lastMoveCommand = 0L;
		_task = ThreadPoolManager.getInstance().scheduleAtFixedRate(this, 1000, 100);
	}

	public void stop()
	{
		if (_task != null)
		{
			_task.cancel(false);
			_task = null;
		}

		_lootTarget = null;
		_lastLootObjectId = 0;
		_lastLootAttempt = 0;
		_lastPotionUse = 0;
		_attackStateSince = 0L;
		_lastTargetObjectId = 0;
		_lastTargetHp = -1.0;
		_lastTargetProgress = 0L;
		_lastMoveCommand = 0L;
	}

	public void setSkills(int skill1, int skill2, int skill3)
	{
		_skill1 = skill1;
		_skill2 = skill2;
		_skill3 = skill3;
		_currentSkill = 1;
	}

	public int getSkill1()
	{
		return _skill1;
	}

	public int getSkill2()
	{
		return _skill2;
	}

	public int getSkill3()
	{
		return _skill3;
	}

	public void setSearchRadius(int radius)
	{
		_searchRadius = radius;
	}

	public int getSearchRadius()
	{
		return _searchRadius;
	}

	@Override
	public void run()
	{
		if (_player == null || !_player.isOnline())
		{
			stop();
			return;
		}

		if (_player.isDead())
			return;

		checkAttackWatchdog();
		checkTargetWatchdog();

		if (_player.isCastingNow())
			return;

		if (tryUsePotion())
			return;

		ItemInstance loot = findNearestLoot();
		if (loot != null)
		{
			if (handleLoot(loot))
				return;
		}

		MonsterInstance target = getCurrentValidTarget();

		if (target == null)
			target = findNearestMonster();

		if (target == null || target.isDead())
			return;

		if (_player.getTarget() != target)
		{
			_player.setTarget(target);
			_lastTargetObjectId = target.getObjectId();
			_lastTargetHp = target.getCurrentHp();
			_lastTargetProgress = System.currentTimeMillis();
		}

		if (_skill1 <= 0 && _skill2 <= 0 && _skill3 <= 0)
		{
			handleNormalAttack(target);
			return;
		}

		if (useAutoSkill(target))
			return;

		if (!target.isDead())
			handleNormalAttack(target);
	}

	private MonsterInstance getCurrentValidTarget()
	{
		GameObject current = _player.getTarget();

		if (!(current instanceof MonsterInstance))
			return null;

		MonsterInstance target = (MonsterInstance) current;

		if (target.isDead() || !target.isVisible())
		{
			resetTargetState();
			_player.setTarget(null);
			return null;
		}

		return target;
	}

	private void resetTargetState()
	{
		_lastTargetObjectId = 0;
		_lastTargetHp = -1.0;
		_lastTargetProgress = System.currentTimeMillis();
	}

	private void checkTargetWatchdog()
	{
		GameObject current = _player.getTarget();

		if (!(current instanceof MonsterInstance))
		{
			_lastTargetObjectId = 0;
			_lastTargetHp = -1.0;
			_lastTargetProgress = System.currentTimeMillis();
			return;
		}

		MonsterInstance target = (MonsterInstance) current;
		if (target.isDead())
		{
			_player.setTarget(null);
			resetTargetState();
			return;
		}

		long now = System.currentTimeMillis();
		double hp = target.getCurrentHp();

		if (target.getObjectId() != _lastTargetObjectId)
		{
			_lastTargetObjectId = target.getObjectId();
			_lastTargetHp = hp;
			_lastTargetProgress = now;
			return;
		}

		if (_lastTargetHp < 0 || hp < _lastTargetHp)
		{
			_lastTargetHp = hp;
			_lastTargetProgress = now;
			return;
		}

		if (now - _lastTargetProgress < TARGET_STUCK_TIMEOUT)
			return;

		if (_player.isAttackingNow())
			_player.abortAttack(false, false);

		_player.setTarget(null);
		resetTargetState();
	}

	private void checkAttackWatchdog()
	{
		if (!_player.isAttackingNow())
		{
			_attackStateSince = 0L;
			return;
		}

		long now = System.currentTimeMillis();

		if (_attackStateSince == 0L)
		{
			_attackStateSince = now;
			return;
		}

		if (now - _attackStateSince < ATTACK_STUCK_TIMEOUT)
			return;

		_player.abortAttack(false, false);
		_player.setTarget(null);
		resetTargetState();
		_attackStateSince = 0L;
	}

	private boolean tryUsePotion()
	{
		String value = _player.getVar("autofarm_potion");

		if (value == null || value.isEmpty())
			return false;

		int potionId;

		try
		{
			potionId = Integer.parseInt(value);
		}
		catch (NumberFormatException e)
		{
			return false;
		}

		if (potionId != 1539 && potionId != 5592 && potionId != 728)
			return false;

		double percent = getResourcePercent(potionId);

		if (percent > 60.0)
			return false;

		long now = System.currentTimeMillis();

		if (now - _lastPotionUse < POTION_INTERVAL)
			return false;

		ItemInstance potion = _player.getInventory().getItemByItemId(potionId);

		if (potion == null)
			return false;

		IItemHandler handler = potion.getTemplate().getHandler();

		if (handler == null)
			return false;

		boolean used = handler.useItem(_player, potion, false);

		if (used)
		{
			_lastPotionUse = now;
			return true;
		}

		return false;
	}

	private double getResourcePercent(int potionId)
	{
		if (potionId == 1539)
		{
			double maxHp = _player.getMaxHp();
			if (maxHp <= 0) return 100.0;
			return (_player.getCurrentHp() * 100.0) / maxHp;
		}

		if (potionId == 5592)
		{
			double maxCp = _player.getMaxCp();
			if (maxCp <= 0) return 100.0;
			return (_player.getCurrentCp() * 100.0) / maxCp;
		}

		if (potionId == 728)
		{
			double maxMp = _player.getMaxMp();
			if (maxMp <= 0) return 100.0;
			return (_player.getCurrentMp() * 100.0) / maxMp;
		}

		return 100.0;
	}

	private void handleNormalAttack(MonsterInstance target)
	{
		if (target == null || target.isDead())
			return;

		if (_player.isAttackingNow())
			return;

		if (_player.isAttackingDisabled())
			return;

		int attackRange = getPhysicalAttackRange();
		double distance = _player.getDistance(target);

		if (distance > attackRange)
		{
			moveToAttackRange(target, attackRange);
			return;
		}

		_player.doAttack(target);
	}

	private int getPhysicalAttackRange()
	{
		WeaponTemplate weapon = _player.getActiveWeaponItem();

		if (weapon != null)
		{
			WeaponType type = weapon.getItemType();

			if (type == WeaponType.BOW || type == WeaponType.CROSSBOW)
				return Math.max(10, weapon.getAttackRange() * 2);
		}

		return Math.max(10, _player.getPhysicalAttackRange());
	}

	private void moveToAttackRange(MonsterInstance target, int range)
	{
		if (target == null || target.isDead())
			return;

		if (_player.isAttackingNow())
			return;

		if (_player.isCastingNow())
			return;

		int offset = Math.max(10, range - 5);
		long now = System.currentTimeMillis();

		if (now - _lastMoveCommand < MOVEMENT_COMMAND_INTERVAL)
			return;

		_player.followToCharacter(target, offset, false);
		_lastMoveCommand = now;
	}

	private boolean useAutoSkill(MonsterInstance target)
	{
		int[] skills = { _skill1, _skill2, _skill3 };
		boolean possuiAlgumaSkillConfigurada = false;

		for (int i = 0; i < skills.length; i++)
		{
			int index = (_currentSkill - 1 + i) % skills.length;
			int skillId = skills[index];

			if (skillId <= 0)
				continue;

			possuiAlgumaSkillConfigurada = true;

			Skill skill = _player.getKnownSkill(skillId);
			if (skill == null)
				continue;

			if (_player.isSkillDisabled(skill))
				continue;

			double mpConsume = skill.getMpConsume();

			if (mpConsume > 0 && _player.getCurrentMp() < mpConsume)
				continue;

			if (skillId == 4)
			{
				if (_player.isAttackingNow())
					_player.abortAttack(false, false);

				_player.doCast(skill, _player, true);
				nextSkill(index);
				return true;
			}

			if (target == null || target.isDead())
				continue;

			if (!skill.isOffensive())
				continue;

			int castRange = Math.max(10, skill.getCastRange());
			double distance = _player.getDistance(target);

			if (distance > castRange)
			{
				if (!_player.isAttackingNow())
				{
					int offset = Math.max(10, castRange - 5);
					_player.followToCharacter(target, offset, false);
				}
				return true;
			}

			if (mpConsume > 0 && _player.getCurrentMp() < mpConsume)
				continue;

			if (_player.isAttackingNow())
				_player.abortAttack(false, false);

			_player.doCast(skill, target, true);

			_currentSkill = index + 2;
			if (_currentSkill > skills.length)
				_currentSkill = 1;

			return true;
		}

		if (possuiAlgumaSkillConfigurada)
		{
			if (_player.isMageClass() || _player.getActiveWeaponItem() == null || _player.getActiveWeaponItem().getItemType() == WeaponType.NONE)
				return true;
		}

		return false;
	}

	private void nextSkill(int index)
	{
		_currentSkill = index + 2;

		if (_currentSkill > 3)
			_currentSkill = 1;
	}

	private ItemInstance findNearestLoot()
	{
		ItemInstance nearest = null;
		double nearestDistance = _searchRadius;

		for (GameObject object : GameObjectsStorage.getAllObjects())
		{
			if (!(object instanceof ItemInstance))
				continue;

			ItemInstance item = (ItemInstance) object;

			if (!item.isVisible())
				continue;

			double distance = _player.getDistance(item);

			if (distance > nearestDistance)
				continue;

			if (item.getObjectId() == _lastLootObjectId)
			{
				if (System.currentTimeMillis() - _lastLootAttempt < 2000)
					continue;
			}

			nearestDistance = distance;
			nearest = item;
		}

		return nearest;
	}

	private boolean handleLoot(ItemInstance item)
	{
		if (item == null || !item.isVisible())
		{
			_lootTarget = null;
			return false;
		}

		_lootTarget = item;
		double distance = _player.getDistance(item);
		final int PICKUP_RANGE = 200;

		if (distance > PICKUP_RANGE)
		{
			long now = System.currentTimeMillis();

			if (now - _lastMoveCommand >= MOVEMENT_COMMAND_INTERVAL)
			{
				_player.moveToLocation(item.getLoc(), 100, true);
				_lastMoveCommand = now;
			}

			return true;
		}

		_player.setTarget(item);
		_lastLootObjectId = item.getObjectId();
		_lastLootAttempt = System.currentTimeMillis();
		_player.doPickupItem(item);
		_lootTarget = null;
		return true;
	}

	private MonsterInstance findNearestMonster()
	{
		MonsterInstance nearest = null;
		double nearestDistance = _searchRadius;

		for (l2f.gameserver.model.instances.NpcInstance npc : GameObjectsStorage.getAllNpcs())
		{
			if (!(npc instanceof MonsterInstance))
				continue;

			MonsterInstance monster = (MonsterInstance) npc;

			if (monster.isDead())
				continue;

			if (!monster.isVisible())
				continue;

			double distance = _player.getDistance(monster);

			if (distance <= nearestDistance)
			{
				nearestDistance = distance;
				nearest = monster;
			}
		}

		return nearest;
	}
}
