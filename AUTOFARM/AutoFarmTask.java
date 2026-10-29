package l2f.gameserver.autofarm;

import java.util.Map;
import java.util.WeakHashMap;
import java.util.concurrent.ScheduledFuture;

import l2f.gameserver.ThreadPoolManager;
import l2f.gameserver.model.GameObject;
import l2f.gameserver.model.GameObjectsStorage;
import l2f.gameserver.model.Player;
import l2f.gameserver.model.Skill;
import l2f.gameserver.model.instances.MonsterInstance;
import l2f.gameserver.model.items.ItemInstance;
import l2f.gameserver.templates.item.WeaponTemplate;
import l2f.gameserver.templates.item.WeaponTemplate.WeaponType;
import l2f.gameserver.utils.Location;

public class AutoFarmTask implements Runnable
{
	private final Player _player;
	private ScheduledFuture<?> _task;
	private boolean _returningToStart = false;
	private int _startX;
	private int _startY;
	private int _startZ;
	private static final Map<Player, AutoFarmTask> ACTIVE_TASKS = new WeakHashMap<Player, AutoFarmTask>();

	private int _searchRadius = 80000;
	private static final long MOVEMENT_COMMAND_INTERVAL = 500L;
	private static final double TARGET_ADVANCE_DISTANCE = 1200.0;

	private int _lastTargetObjectId = 0;
	private long _lastMonsterScan = 0L;
	private MonsterInstance _cachedMonster = null;
	private long _lastLootScan = 0L;
	private ItemInstance _cachedLoot = null;
	private static final long MONSTER_SCAN_INTERVAL = 100L;
	private static final long LOOT_SCAN_INTERVAL = 700L;
	private long _lastThreatScan = 0L;
	private MonsterInstance _cachedThreat = null;
	private static final long THREAT_SCAN_INTERVAL = 200L;

	private int _skill1 = 0;
	private int _skill2 = 0;
	private int _skill3 = 0;
	private int _currentSkill = 1;

	private ItemInstance _lootTarget;
	private int _lastLootObjectId = 0;
	private long _lastLootAttempt = 0;

	private long _lastMoveCommand = 0L;

	private long _attackStateSince = 0L;
	private long _targetProgressSince = 0L;
	private double _lastTargetDistance = -1.0;
	private long _noTargetSince = 0L;
	private long _lastAutomaticRecovery = 0L;
	private int _stuckCount = 0;
	private int _ignoredTargetObjectId = 0;
	private long _ignoredTargetUntil = 0L;

	private static final long ATTACK_STUCK_TIMEOUT = 3500L;
	private static final long TARGET_PROGRESS_TIMEOUT = 4000L;
	private static final long MAGE_TARGET_PROGRESS_TIMEOUT = 6000L;
	private static final long NO_TARGET_TIMEOUT = 7000L;
	private static final long AUTO_RETURN_COOLDOWN = 10000L;
	private static final long IGNORED_TARGET_TIMEOUT = 10000L;
	private static final int MAX_STUCK_BEFORE_RETURN = 2;
	private static final double TARGET_PROGRESS_MIN_DISTANCE = 100.0;

	public AutoFarmTask(Player player)
	{
		_player = player;
	}

	public void start()
	{
		if (_task != null)
			return;

		_startX = _player.getX();
		_startY = _player.getY();
		_startZ = _player.getZ();
		_returningToStart = false;
		_lastMoveCommand = 0L;
		_noTargetSince = 0L;
		_lastAutomaticRecovery = 0L;
		_stuckCount = 0;
		_ignoredTargetObjectId = 0;
		_ignoredTargetUntil = 0L;
		_lastMonsterScan = 0L;
		_cachedMonster = null;
		_lastLootScan = 0L;
		_cachedLoot = null;
		synchronized (ACTIVE_TASKS)
		{
			ACTIVE_TASKS.put(_player, this);
		}
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
		_cachedLoot = null;
		_cachedMonster = null;
		_lastMonsterScan = 0L;
		_lastLootScan = 0L;
		_lastLootObjectId = 0;
		_lastLootAttempt = 0;
		_attackStateSince = 0L;
		_targetProgressSince = 0L;
		_lastTargetDistance = -1.0;
		_lastMoveCommand = 0L;
		_returningToStart = false;
		_noTargetSince = 0L;
		_lastAutomaticRecovery = 0L;
		_stuckCount = 0;
		_ignoredTargetObjectId = 0;
		_ignoredTargetUntil = 0L;
		synchronized (ACTIVE_TASKS)
		{
			if (ACTIVE_TASKS.get(_player) == this)
				ACTIVE_TASKS.remove(_player);
		}
	}

	public static boolean returnToStart(Player player)
	{
		if (player == null)
			return false;
		AutoFarmTask task;
		synchronized (ACTIVE_TASKS)
		{
			task = ACTIVE_TASKS.get(player);
		}
		if (task == null || task._task == null)
			return false;
		task._returningToStart = true;
		task._lastMoveCommand = 0L;
		task._lastTargetObjectId = 0;
		task._targetProgressSince = 0L;
		task._lastTargetDistance = -1.0;
		task._lootTarget = null;
		if (player.isAttackingNow())
			player.abortAttack(false, false);
		player.setTarget(null);
		return true;
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
		if (_returningToStart)
		{
			handleReturnToStart();
			return;
		}

		MonsterInstance threat = findThreateningMonster();
		if (threat != null && !_player.isDead())
		{
			GameObject currentTarget = _player.getTarget();
			if (currentTarget != threat)
			{
				if (_player.isAttackingNow())
					_player.abortAttack(false, false);
				_lootTarget = null;
				_lastMoveCommand = 0L;
				_targetProgressSince = 0L;
				_lastTargetDistance = -1.0;
				_player.setTarget(threat);
			}
		}

		if (_player.isCastingNow())
			return;

		// Ranged e magos ficam no ponto de ativação; não caminham para coletar drops.
		ItemInstance loot = threat == null && !isStationaryRangedPlayer() ? findNearestLoot() : null;
		if (loot != null)
		{
			if (handleLoot(loot))
				return;
		}

		MonsterInstance target = threat;
		if (target == null)
			target = getCurrentValidTarget();

		if (target == null)
			target = findNextMonster();

		if (target == null || target.isDead())
		{
			if (_noTargetSince == 0L)
				_noTargetSince = System.currentTimeMillis();
			else if (System.currentTimeMillis() - _noTargetSince >= NO_TARGET_TIMEOUT)
				triggerAutomaticRecovery();

			return;
		}

		_noTargetSince = 0L;

		if (_player.getTarget() != target)
		{
			_player.setTarget(target);
		}

		int targetObjectId = target.getObjectId();
		if (_lastTargetObjectId != targetObjectId)
		{
			// Cada alvo ganha seu próprio período de progresso; não herda
			// o temporizador do monstro anterior.
			_lastTargetObjectId = targetObjectId;
			_targetProgressSince = System.currentTimeMillis();
			_lastTargetDistance = _player.getDistance(target);
			_attackStateSince = 0L;
			// Não zera _stuckCount aqui: trocar de alvo após uma falha não
			// pode impedir a recuperação automática repetidamente.
		}

		if (checkTargetProgress(target))
			return;

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

	private void handleReturnToStart()
	{
		double distance = _player.getDistance(_startX, _startY, _startZ);
		if (distance <= 100.0)
		{
			_returningToStart = false;
			_lastMoveCommand = 0L;
			_targetProgressSince = 0L;
			_lastTargetDistance = -1.0;
			_noTargetSince = 0L;
			_stuckCount = 0;
			_ignoredTargetObjectId = 0;
			_ignoredTargetUntil = 0L;
			return;
		}
		long now = System.currentTimeMillis();
		if (now - _lastMoveCommand < MOVEMENT_COMMAND_INTERVAL)
			return;
		_player.moveToLocation(new Location(_startX, _startY, _startZ), 0, true);
		_lastMoveCommand = now;
	}

	private MonsterInstance getCurrentValidTarget()
	{
		GameObject current = _player.getTarget();

		if (!(current instanceof MonsterInstance))
			return null;

		MonsterInstance target = (MonsterInstance) current;

		if (target.isDead() || !target.isVisible())
		{
			_player.setTarget(null);
			return null;
		}

		return target;
	}

	private void checkAttackWatchdog()
	{
		if (!_player.isAttackingNow())
		{
			_attackStateSince = 0L;
			return;
		}

		// Atacar continuamente um alvo válido é comportamento normal.
		// Não aborta a cada 3,5 s só porque o ataque permanece ativo.
		GameObject current = _player.getTarget();
		if (current instanceof MonsterInstance)
		{
			MonsterInstance target = (MonsterInstance) current;
			if (!target.isDead() && target.isVisible()
				&& _player.getDistance(target) <= getPhysicalAttackRange() + 80)
			{
				_attackStateSince = System.currentTimeMillis();
				return;
			}
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
		_attackStateSince = 0L;
		_stuckCount++;

		if (_stuckCount >= MAX_STUCK_BEFORE_RETURN)
			triggerAutomaticRecovery();
	}

	private boolean checkTargetProgress(MonsterInstance target)
	{
		if (target == null || target.isDead() || !target.isVisible())
			return false;

		long now = System.currentTimeMillis();
		double distance = _player.getDistance(target);

		// Ficar parado é normal durante o combate corpo a corpo. Não marque
		// o alvo como travado só porque a distância não está diminuindo.
		int physicalRange = getPhysicalAttackRange();
		boolean inAttackRange = distance <= physicalRange + 80.0;

		if (!inAttackRange)
		{
			int[] configuredSkills = { _skill1, _skill2, _skill3 };
			for (int skillId : configuredSkills)
			{
				if (skillId <= 0)
					continue;

				Skill skill = _player.getKnownSkill(skillId);
				if (skill != null && skill.isOffensive()
					&& distance <= Math.max(10, skill.getCastRange()) + 80.0)
				{
					inAttackRange = true;
					break;
				}
			}
		}

		if (inAttackRange && (_player.isAttackingNow() || _player.isCastingNow()))
		{
			// Só considera progresso real quando há uma ação de combate em curso.
			// Estar ao lado do monstro sem atacar também precisa disparar recuperação.
			_targetProgressSince = now;
			_lastTargetDistance = distance;
			_stuckCount = 0;
			return false;
		}

		if (_lastTargetDistance < 0.0 || distance < _lastTargetDistance - TARGET_PROGRESS_MIN_DISTANCE)
		{
			_lastTargetDistance = distance;
			_targetProgressSince = now;
			_stuckCount = 0;
			return false;
		}

		if (_targetProgressSince == 0L)
		{
			_targetProgressSince = now;
			_lastTargetDistance = distance;
			return false;
		}

		long progressTimeout = _player.isMageClass() ? MAGE_TARGET_PROGRESS_TIMEOUT : TARGET_PROGRESS_TIMEOUT;
		if (now - _targetProgressSince < progressTimeout)
			return false;

		// Um alvo fora do alcance não deve fazer arqueiros/magos perseguirem ou
		// dispararem retorno automático. Descarta-o por alguns segundos.
		if (isStationaryRangedPlayer())
		{
			if (_player.isAttackingNow())
				_player.abortAttack(false, false);
			_player.setTarget(null);
			_lastTargetObjectId = 0;
			_ignoredTargetObjectId = target.getObjectId();
			_ignoredTargetUntil = now + IGNORED_TARGET_TIMEOUT;
			_targetProgressSince = 0L;
			_lastTargetDistance = -1.0;
			_attackStateSince = 0L;
			_stuckCount = 0;
			if (_cachedMonster == target)
				_cachedMonster = null;
			return true;
		}

		if (_player.isAttackingNow())
			_player.abortAttack(false, false);

		_player.setTarget(null);
		_lastTargetObjectId = 0;
		_ignoredTargetObjectId = target.getObjectId();
		_ignoredTargetUntil = now + IGNORED_TARGET_TIMEOUT;
		_targetProgressSince = 0L;
		_lastTargetDistance = -1.0;
		_lastMoveCommand = 0L;
		_stuckCount++;

		if (_stuckCount >= MAX_STUCK_BEFORE_RETURN)
			triggerAutomaticRecovery();

		return true;
	}

	private void triggerAutomaticRecovery()
	{
		long now = System.currentTimeMillis();

		if (_returningToStart)
			return;

		if (now - _lastAutomaticRecovery < AUTO_RETURN_COOLDOWN)
			return;

		double distanceFromStart = _player.getDistance(_startX, _startY, _startZ);

		// Em classes de ataque à distância, falta de alvo ou alvo inalcançável
		// nunca deve iniciar movimento automático de retorno.
		if (isStationaryRangedPlayer())
		{
			_stuckCount = 0;
			_targetProgressSince = 0L;
			_lastTargetDistance = -1.0;
			_noTargetSince = now;
			return;
		}

		if (distanceFromStart <= 500.0)
		{
			_stuckCount = 0;
			_noTargetSince = now;
			_targetProgressSince = 0L;
			_lastTargetDistance = -1.0;
			return;
		}

		_lastAutomaticRecovery = now;
		_returningToStart = true;
		_lastMoveCommand = 0L;
		_lastTargetObjectId = 0;
		_targetProgressSince = 0L;
		_lastTargetDistance = -1.0;
		_noTargetSince = 0L;
		_ignoredTargetObjectId = 0;
		_ignoredTargetUntil = 0L;
		_lootTarget = null;

		if (_player.isAttackingNow())
			_player.abortAttack(false, false);

		_player.setTarget(null);
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

		// Tolerância pequena evita oscilar entre mover e atacar na borda do alcance.
		if (distance > attackRange + 20.0)
		{
			if (isStationaryRangedPlayer())
				return;
			moveToAttackRange(target, attackRange);
			return;
		}

		_player.doAttack(target);
	}

	private boolean isStationaryRangedPlayer()
	{
		if (_player == null)
			return false;

		if (_player.isMageClass())
			return true;

		WeaponTemplate weapon = _player.getActiveWeaponItem();
		if (weapon == null)
			return false;

		WeaponType type = weapon.getItemType();
		return type == WeaponType.BOW || type == WeaponType.CROSSBOW;
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
		if (isStationaryRangedPlayer())
			return;

		if (target == null || target.isDead())
			return;

		if (_player.isAttackingNow())
			return;

		if (_player.isCastingNow())
			return;

		WeaponTemplate weapon = _player.getActiveWeaponItem();
		boolean rangedWeapon = weapon != null
			&& (weapon.getItemType() == WeaponType.BOW || weapon.getItemType() == WeaponType.CROSSBOW);
		// Em melee, aproximar um pouco mais compensa o raio de colisão dos personagens.
		int offset = rangedWeapon ? Math.max(10, range - 5) : Math.max(0, range - 50);
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
		int preferredCastRange = 0;

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
				// Não seguir por causa da primeira skill fora de alcance:
				// verifica antes se outra skill pode ser usada agora.
				preferredCastRange = Math.max(preferredCastRange, castRange);
				continue;
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

		if (preferredCastRange > 0 && target != null && !target.isDead())
		{
			if (isStationaryRangedPlayer())
				return _player.getDistance(target) > getPhysicalAttackRange() + 20.0;

			if (!_player.isAttackingNow() && !_player.isCastingNow())
			{
				int offset = Math.max(10, preferredCastRange - 5);
				long now = System.currentTimeMillis();
				if (now - _lastMoveCommand >= MOVEMENT_COMMAND_INTERVAL)
				{
					_player.followToCharacter(target, offset, false);
					_lastMoveCommand = now;
				}
			}
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
		long now = System.currentTimeMillis();
		if (now - _lastLootScan < LOOT_SCAN_INTERVAL)
		{
			if (_cachedLoot != null && _cachedLoot.isVisible()
				&& _player.getDistance(_cachedLoot) <= _searchRadius)
				return _cachedLoot;
			return null;
		}
		_lastLootScan = now;
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

		_cachedLoot = nearest;
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

	/**
	 * Prioriza monstros cujo alvo atual seja este jogador.
	 * O cache evita varrer todos os NPCs em cada tick de 100 ms.
	 */
	private MonsterInstance findThreateningMonster()
	{
		long now = System.currentTimeMillis();

		if (_cachedThreat != null)
		{
			if (!_cachedThreat.isDead() && _cachedThreat.isVisible()
				&& _cachedThreat.getTarget() == _player
				&& _player.getDistance(_cachedThreat) <= _searchRadius)
			{
				if (now - _lastThreatScan < THREAT_SCAN_INTERVAL)
					return _cachedThreat;
			}
			else
			{
				// Uma ameaça inválida não deve bloquear uma nova busca.
				_cachedThreat = null;
				_lastThreatScan = 0L;
			}
		}

		if (now - _lastThreatScan < THREAT_SCAN_INTERVAL)
			return null;

		_lastThreatScan = now;
		MonsterInstance nearest = null;
		double nearestDistance = _searchRadius;

		for (l2f.gameserver.model.instances.NpcInstance npc : GameObjectsStorage.getAllNpcsForIterate())
		{
			if (!(npc instanceof MonsterInstance))
				continue;

			MonsterInstance monster = (MonsterInstance) npc;

			if (monster.isDead() || !monster.isVisible() || monster.getTarget() != _player)
				continue;

			double distance = _player.getDistance(monster);

			if (distance > _searchRadius || distance >= nearestDistance)
				continue;

			nearestDistance = distance;
			nearest = monster;
		}

		_cachedThreat = nearest;
		return nearest;
	}

	private MonsterInstance findNextMonster()
	{
		long now = System.currentTimeMillis();

		if (_cachedMonster != null)
		{
			if (!_cachedMonster.isDead() && _cachedMonster.isVisible()
				&& _player.getDistance(_cachedMonster) <= _searchRadius
				&& (_cachedMonster.getObjectId() != _ignoredTargetObjectId || now >= _ignoredTargetUntil))
			{
				// Reusa imediatamente o alvo válido. A busca só roda quando
				// precisamos substituir o alvo atual, não para trocar por outro.
				return _cachedMonster;
			}

			_cachedMonster = null;
			_lastMonsterScan = 0L;
		}

		// Sem alvo em cache, tenta novamente em cada ciclo (100 ms).
		if (now - _lastMonsterScan < MONSTER_SCAN_INTERVAL)
			return null;

		_lastMonsterScan = now;
		MonsterInstance nearest = null;
		double nearestDistance = _searchRadius;

		for (l2f.gameserver.model.instances.NpcInstance npc : GameObjectsStorage.getAllNpcsForIterate())
		{
			if (!(npc instanceof MonsterInstance))
				continue;

			MonsterInstance monster = (MonsterInstance) npc;

			if (monster.isDead() || !monster.isVisible())
				continue;

			int objectId = monster.getObjectId();

			if (objectId == _ignoredTargetObjectId)
			{
				if (now < _ignoredTargetUntil)
					continue;

				_ignoredTargetObjectId = 0;
				_ignoredTargetUntil = 0L;
			}

			double distance = _player.getDistance(monster);

			if (distance > _searchRadius || distance >= nearestDistance)
				continue;

			nearestDistance = distance;
			nearest = monster;
		}

		_cachedMonster = nearest;
		return nearest;
	}
}