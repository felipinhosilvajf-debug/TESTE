package l2f.gameserver.handler.admincommands.impl;

import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.CopyOnWriteArrayList;
import java.util.concurrent.ScheduledFuture;
import java.util.concurrent.ThreadLocalRandom;

import l2f.commons.util.Rnd;
import l2f.gameserver.ThreadPoolManager;
import l2f.gameserver.handler.admincommands.IAdminCommandHandler;
import l2f.gameserver.model.GameObjectsStorage;
import l2f.gameserver.model.Player;
import l2f.gameserver.utils.Location;

public class AdminFakePlayers implements IAdminCommandHandler
{
	private static final int MAX_BOTS_PER_COMMAND = 20;
	private static final List<Player> _fakeBots = new CopyOnWriteArrayList<Player>();
	private static final Map<Player, ScheduledFuture<?>> _movementTasks = new ConcurrentHashMap<Player, ScheduledFuture<?>>();

	private static final String[] _names =
	{
		"CleitonRasta", "FabioLigh", "LordKauan", "DarkMito", "NegoRush",
		"BruxoDoPVP", "LethalBR", "MagoLoko", "TiozinDoFarm", "KillerZika",
		"RafaOwna", "D4rkKnight", "SombraBR", "FenixDoSul", "LoboNoturno",
		"XxMatadorxX", "Caveirinha", "TropaDoH5", "NinjaLoko", "MlkDoPVP",
		"ViniCritical", "RatoDoFarm", "LordVengeance", "MestreKiller", "FatalityBR",
		"ReiDoDrop", "MalucoX", "TerrorDoH5", "ShadowRasta", "BalaDePrata",
		"ZehRuela", "BraboDoFarm", "DkzinBR", "SoulHunter", "ToxicPlayer",
		"ArcanjoNegro", "MestreDoCaos", "Pvpzeiro", "Rushador", "CoringaBR",
		"BlackRaven", "KauanX7", "LendarioBR", "SemCooldown", "HeadShotBR",
		"FuriaNoturna", "MlkDoRush", "SaciPVP", "RagnarBR", "F4bioL1ght",
		"Lordzin", "XxCaveiraxX", "CriticoBR", "DreadSoul", "Ligeirinho",
		"ReaperBR", "BielzinPvP", "DonoDoPedaco", "NoiadoH5", "VortexBR",
		"DarkCleiton", "ZikaDoH5", "TsunamiBR", "MortalRush", "FrostKiller",
		"SeuPiorPesadelo", "KillerDoVale", "DrakeBR", "BrutalMage", "RastaWarrior"
	};

	private static enum Commands
	{
		admin_create_fake_players,
		admin_remove_fake_players;
	}

	@Override
	public boolean useAdminCommand(Enum comm, String[] wordList, String fullString, Player activeChar)
	{
		Commands command = (Commands) comm;

		if (!activeChar.getPlayerAccess().CanReload)
			return false;

		switch (command)
		{
			case admin_create_fake_players:
				int count = 1;
				if (wordList.length > 1)
				{
					try
					{
						count = Integer.parseInt(wordList[1]);
					}
					catch (NumberFormatException e)
					{
						activeChar.sendMessage("Uso: //create_fake_players [quantidade]");
						return false;
					}
				}

				if (count < 1 || count > MAX_BOTS_PER_COMMAND)
				{
					activeChar.sendMessage("Informe uma quantidade entre 1 e " + MAX_BOTS_PER_COMMAND + ".");
					return false;
				}

				int created = 0;
				for (int i = 0; i < count; i++)
				{
					String botName = getRandomAvailableName();
					if (botName == null)
					{
						activeChar.sendMessage("Nao foi possivel encontrar mais nomes livres.");
						break;
					}

					Player bot = Player.createFakeBot(botName);
					bot.spawnMe(Location.findPointToStay(activeChar, 50, 100));
					_fakeBots.add(bot);
					created++;

					// Uma parte fica parada; os demais caminham aleatoriamente pela area.
					if (ThreadLocalRandom.current().nextInt(100) >= 30)
						startRandomMovement(bot);
				}

				activeChar.sendMessage("Bots temporarios criados: " + created + ".");
				break;

			case admin_remove_fake_players:
				int removed = 0;
				for (Player bot : _fakeBots)
				{
					ScheduledFuture<?> task = _movementTasks.remove(bot);
					if (task != null)
						task.cancel(false);

					if (bot.isVisible())
						bot.decayMe();
					bot.deleteMe();
					_fakeBots.remove(bot);
					removed++;
				}

				activeChar.sendMessage("Bots temporarios removidos: " + removed + ".");
				break;
		}

		return true;
	}

	private static String getRandomAvailableName()
	{
		for (int attempt = 0; attempt < _names.length * 2; attempt++)
		{
			String name = _names[Rnd.get(_names.length)];
			if (GameObjectsStorage.getPlayer(name) == null)
				return name;
		}
		return null;
	}

	private static void startRandomMovement(final Player bot)
	{
		long initialDelay = ThreadLocalRandom.current().nextLong(2000L, 8000L);
		long interval = ThreadLocalRandom.current().nextLong(6000L, 12000L);

		ScheduledFuture<?> task = ThreadPoolManager.getInstance().scheduleAtFixedRate(new Runnable()
		{
			@Override
			public void run()
			{
				if (!bot.isVisible() || !bot.isFakePlayer())
					return;

				// Pausas aleatorias deixam o movimento menos mecanico.
				if (ThreadLocalRandom.current().nextInt(100) < 40)
					return;

				Location destination = Location.findPointToStay(bot, 100, 350);
				bot.moveToLocation(destination, 0, true);
			}
		}, initialDelay, interval);

		_movementTasks.put(bot, task);
	}

	@Override
	public Enum[] getAdminCommandEnum()
	{
		return Commands.values();
	}
}
