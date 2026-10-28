package l2f.gameserver.handler.admincommands.impl;

import l2f.gameserver.handler.admincommands.IAdminCommandHandler;
import l2f.gameserver.model.Player;
import l2f.gameserver.model.base.InvisibleType;
import l2f.gameserver.network.serverpackets.CameraMode;
import l2f.gameserver.network.serverpackets.ExStartScenePlayer;
import l2f.gameserver.network.serverpackets.SpecialCamera;

public class AdminCamera implements IAdminCommandHandler
{
	private static enum Commands
	{
		admin_freelook,
		admin_cinematic
	}

	@Override
	public boolean useAdminCommand(@SuppressWarnings("rawtypes") Enum comm, String[] wordList, String fullString, Player activeChar)
	{
		Commands command = (Commands) comm;

		if (!activeChar.getPlayerAccess().Menu)
			return false;

		switch (command)
		{
			case admin_freelook:
			{
				if (fullString.length() > 15)
					fullString = fullString.substring(15);
				else
				{
					activeChar.sendMessage("Usage: //freelook 1 or //freelook 0");
					return false;
				}

				int mode = Integer.parseInt(fullString);

				if (mode == 1)
				{
					activeChar.setInvisibleType(InvisibleType.NORMAL);
					activeChar.setIsInvul(true);
					activeChar.setNoChannel(-1);
					activeChar.setFlying(true);
				}
				else
				{
					activeChar.setInvisibleType(InvisibleType.NONE);
					activeChar.setIsInvul(false);
					activeChar.setNoChannel(0);
					activeChar.setFlying(false);
				}

				activeChar.sendPacket(new CameraMode(mode));

				break;
			}

			case admin_cinematic:
			{
				activeChar.sendMessage("ADMIN_CINEMATIC FOI EXECUTADO!");

				activeChar.sendPacket(
					new ExStartScenePlayer(
						ExStartScenePlayer.SCENE_SSQ2_ELYSS_NARRATION
					)
				);

				activeChar.sendMessage("PACOTE FREYA ENVIADO!");

				break;
			}
		}

		return true;
	}

	@SuppressWarnings("rawtypes")
	@Override
	public Enum[] getAdminCommandEnum()
	{
		return Commands.values();
	}
}
