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
import l2f.gameserver.model.World;
import l2f.gameserver.model.base.Experience;
import l2f.gameserver.model.instances.MonsterInstance;
import l2f.gameserver.model.instances.NpcInstance;
import l2f.gameserver.network.serverpackets.Say2;
import l2f.gameserver.network.serverpackets.components.ChatType;
import l2f.gameserver.utils.Location;

public class AdminFakePlayers implements IAdminCommandHandler
{
	private static final int MAX_BOTS_PER_COMMAND = 100;
	private static final List<Player> _fakeBots = new CopyOnWriteArrayList<Player>();
	private static final Map<Player, ScheduledFuture<?>> _movementTasks = new ConcurrentHashMap<Player, ScheduledFuture<?>>();
	private static final Map<Player, BotMode> _botModes = new ConcurrentHashMap<Player, BotMode>();

	// Templates iniciais de cada raca. Os IDs sao ClassId da source H5.
	private static final int[] _classTemplates = { 0, 10, 18, 25, 31, 38, 44, 49, 53, 123, 124 };

	private static final String[] _names =
	{
		"CleitonRasta", "FabioLigh", "LordKauan", "DarkMito", "NegoRush", "BruxoDoPVP", "LethalBR", "MagoLoko",
		"TiozinDoFarm", "KillerZika", "RafaOwna", "D4rkKnight", "SombraBR", "FenixDoSul", "LoboNoturno",
		"XxMatadorxX", "Caveirinha", "TropaDoH5", "NinjaLoko", "MlkDoPVP", "ViniCritical", "RatoDoFarm",
		"LordVengeance", "MestreKiller", "FatalityBR", "ReiDoDrop", "MalucoX", "TerrorDoH5", "ShadowRasta",
		"BalaDePrata", "ZehRuela", "BraboDoFarm", "DkzinBR", "SoulHunter", "ToxicPlayer", "ArcanjoNegro",
		"MestreDoCaos", "Pvpzeiro", "Rushador", "CoringaBR", "BlackRaven", "KauanX7", "LendarioBR",
		"SemCooldown", "HeadShotBR", "FuriaNoturna", "MlkDoRush", "SaciPVP", "RagnarBR", "F4bioL1ght",
		"Lordzin", "XxCaveiraxX", "CriticoBR", "DreadSoul", "Ligeirinho", "ReaperBR", "BielzinPvP",
		"DonoDoPedaco", "NoiadoH5", "VortexBR", "DarkCleiton", "ZikaDoH5", "TsunamiBR", "MortalRush",
		"FrostKiller", "SeuPiorPesadelo", "KillerDoVale", "DrakeBR", "BrutalMage", "RastaWarrior",
		"XxDarkWolfxX", "AdenaHunter", "SoulReaperBR", "MagoDaLua", "ArqueiroX", "DwarfMaster", "ElfQueen",
		"DarkPrincess", "OrcFurious", "KamaelBR", "ValkyriaH5", "LordOfAden", "RageQuitBR", "NoobSupremo",
		"CriticalHit", "FarmadorX", "PaoComMortadela", "TioDoBuff", "SemPiedade", "LendaViva", "MisterDrop",
		"PvPNaVeia", "DonaDoPVP", "MagoSombrio", "FlechaMortal", "MachadoBruto", "EspadachimBR",
		"AnjoCaido", "DemonHunter", "CavaleiroReal", "BruxaNegra", "Arcanista", "ReiDosOrcs",
		"Anazinha", "L2Veterano", "SnipeBR", "RushDoido", "KillerFox", "D4rkAngel", "RunaVermelha",
		"BluePhoenix", "NightCrawler", "SilverArrow", "IronFistBR", "MestreDoDrop", "AdenaMan",
		"VemNoX1", "FullCrit", "SemLag", "L2Raiz", "PvpLendario", "SombraDaNoite", "WarriorSoul",
		"ElfoMisterioso", "AnazinhaBR", "KamaelSoul", "OrcDoido", "AnaoMercador", "MercadorAden",
		"PotionSeller", "ShotExpress", "LojaDoCleiton", "ComproTudo", "VendoBuff", "FarmKing",
		"MobHunter", "SoloPlayer", "PartyFinder", "ClanRecruit", "AdenTrader", "BlackKnightBR",
		"WhiteMageBR", "RedRanger", "GoldenDwarf", "DarkElfBR", "OrcChampion", "KamaelHunter",
		"ValkyrieBR", "RogueMaster", "HealerBR", "SpellSingerBR", "TankDoido", "DuelistaX",
		"NecroLoko", "SummonMaster", "Critador", "PvpDaMadruga", "MitoDoH5", "FarmSemFim",
		"BossHunterBR", "DropRaro", "AdenaRush", "GuerreiroX", "MagaLunar", "ArqueiraBR",
		"FacaNaBota", "CajadoArcano", "EscudoDeAden", "CavaleiraX", "BerserkerBR", "SoulBreaker",
		"BlackArrow", "WhiteRaven", "RunaMistica", "FenixNegra", "FenixDourada", "TropaDoDrop",
		"LordDoFarm", "VendedorRaiz", "LojaDaVila", "MestreArtesao", "AnãoDoCraft", "CraftMaster",
		"CacadorDeMobs", "PegaDrop", "CuraRapida", "BuffMeBR", "SemMedo", "PvpOuNada",
		"ShadowBlade", "ArcaneStorm", "SteelFist", "SilentKiller", "BlueRanger", "DarkMystic",
		"WildOrc", "DwarfCrafter", "ElfWalker", "KamaelRage", "HumanKnight", "MysticBR"
	};

	private static final String[] _chatLines =
	{
		"Alguem sabe onde fica o NPC?", "Procurando party para farm.", "Boa noite, pessoal!",
		"Vendendo e comprando itens.", "Essa area ta boa para farmar.", "Alguem tem buff?",
		"Volto ja.", "Bora farmar!", "Tem vaga na party?", "Bom jogo a todos!"
	};

	private static enum BotMode
	{
		MIXED,
		TOWN,
		FARM,
		SOCIAL
	}

	private static enum Commands
	{
		admin_create_fake_players,
		admin_create_social_fake_players,
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
						activeChar.sendMessage("Uso: //create_fake_players [quantidade] [mixed|town|farm] ou //create_social_fake_players [quantidade]");
						return false;
					}
				}

				if (count < 1 || count > MAX_BOTS_PER_COMMAND)
				{
					activeChar.sendMessage("Informe uma quantidade entre 1 e " + MAX_BOTS_PER_COMMAND + ".");
					return false;
				}

				BotMode requestedMode = command == Commands.admin_create_social_fake_players ? BotMode.SOCIAL : parseMode(wordList.length > 2 ? wordList[2] : "mixed");
				if (requestedMode == null || (command == Commands.admin_create_fake_players && requestedMode == BotMode.SOCIAL))
				{
					activeChar.sendMessage("Modo invalido. Use mixed, town ou farm. Para bots que falam, use //create_social_fake_players.");
					return false;
				}

				int created = 0;
				for (int i = 0; i < count; i++)
				{
					String botName = getRandomAvailableName();
					if (botName == null)
					{
						activeChar.sendMessage("Acabaram os nomes livres. Adicione mais nomes ao sistema.");
						break;
					}

					int classId = _classTemplates[Rnd.get(_classTemplates.length)];
					boolean female = ThreadLocalRandom.current().nextBoolean();
					// Kamael possui templates base separados para masculino e feminino.
					if (classId == 123 || classId == 124)
						female = false;

					Player bot = Player.createFakeBot(botName, classId, female);
					int level = ThreadLocalRandom.current().nextInt(20, 86);
					bot.setExp(Experience.LEVEL[level]);
					BotMode mode = requestedMode == BotMode.MIXED ? chooseMode() : requestedMode;
					bot.spawnMe(Location.findPointToStay(activeChar, 50, 100));
					_fakeBots.add(bot);
					_botModes.put(bot, mode);
					created++;
					startBehavior(bot);
				}

				activeChar.sendMessage("Bots temporarios criados: " + created + " (modo " + requestedMode.name().toLowerCase() + ").");
				break;

			case admin_remove_fake_players:
				int removed = 0;
				for (Player bot : _fakeBots)
				{
					ScheduledFuture<?> task = _movementTasks.remove(bot);
					if (task != null)
						task.cancel(false);

					_botModes.remove(bot);
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

	private static BotMode parseMode(String value)
	{
		if ("mixed".equalsIgnoreCase(value))
			return BotMode.MIXED;
		if ("town".equalsIgnoreCase(value))
			return BotMode.TOWN;
		if ("farm".equalsIgnoreCase(value))
			return BotMode.FARM;
		return null;
	}

	private static BotMode chooseMode()
	{
		int roll = ThreadLocalRandom.current().nextInt(100);
		if (roll < 45)
			return BotMode.TOWN;
		if (roll < 75)
			return BotMode.FARM;
		return BotMode.MIXED;
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

	private static void startBehavior(final Player bot)
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

				BotMode mode = _botModes.get(bot);
				if (mode == null)
					return;

				// Alguns bots descansam; outros falam ocasionalmente.
				if (ThreadLocalRandom.current().nextInt(100) < 12 && !bot.isSitting() && !bot.isMoving)
					bot.sitDown(null);
				else if (bot.isSitting() && ThreadLocalRandom.current().nextInt(100) < 45)
					bot.standUp();

				if (mode == BotMode.SOCIAL && ThreadLocalRandom.current().nextInt(100) < 30)
				{
					String line = _chatLines[ThreadLocalRandom.current().nextInt(_chatLines.length)];
					bot.broadcastPacket(new Say2(bot, ChatType.ALL, line));
				}

				if (mode == BotMode.FARM && tryAttackNearbyMonster(bot))
					return;

				if (mode == BotMode.TOWN && ThreadLocalRandom.current().nextInt(100) < 65)
					return;
				if (mode == BotMode.SOCIAL && ThreadLocalRandom.current().nextInt(100) < 35)
					return;
				if (ThreadLocalRandom.current().nextInt(100) < 40)
					return;

				if (bot.isSitting())
					return;

				Location destination = Location.findPointToStay(bot, 100, mode == BotMode.FARM ? 450 : 350);
				bot.moveToLocation(destination, 0, true);
			}
		}, initialDelay, interval);

		_movementTasks.put(bot, task);
	}

	private static boolean tryAttackNearbyMonster(Player bot)
	{
		for (NpcInstance npc : World.getAroundNpc(bot, 700, 300))
		{
			if (!(npc instanceof MonsterInstance) || npc.isDead() || !npc.isAutoAttackable(bot))
				continue;

			MonsterInstance monster = (MonsterInstance) npc;
			if (monster.isRaid())
				continue;

			bot.getAI().Attack(monster, false, false);
			return true;
		}
		return false;
	}

	@Override
	public Enum[] getAdminCommandEnum()
	{
		return Commands.values();
	}
}
