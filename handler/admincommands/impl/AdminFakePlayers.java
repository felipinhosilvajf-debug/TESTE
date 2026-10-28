package l2f.gameserver.handler.admincommands.impl;

import l2f.gameserver.handler.admincommands.IAdminCommandHandler;
import l2f.gameserver.model.GameObjectsStorage;
import l2f.gameserver.model.Player;
import l2f.gameserver.utils.Location;

public class AdminFakePlayers implements IAdminCommandHandler
{
	private static int _nextBotId = 1;

	private static enum Commands
	{
		admin_create_fake_players;
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

				if (count < 1 || count > 20)
				{
					activeChar.sendMessage("Informe uma quantidade entre 1 e 20.");
					return false;
				}

				for (int i = 0; i < count; i++)
				{
					String botName;
					do
					{
						botName = "VB_Bot" + _nextBotId++;
					}
					while (GameObjectsStorage.getPlayer(botName) != null);

					Player bot = Player.createFakeBot(botName);
					bot.spawnMe(Location.findPointToStay(activeChar, 50, 100));
				}

				activeChar.sendMessage("Bots temporarios criados: " + count);
				break;
		}

		return true;
	}

	@Override
	public Enum[] getAdminCommandEnum()
	{
		return Commands.values();
	}
}
