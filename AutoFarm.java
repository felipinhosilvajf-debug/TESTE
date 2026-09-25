package l2f.gameserver.handler.voicecommands.impl;

import l2f.gameserver.autofarm.AutoFarmCommunity;
import l2f.gameserver.handler.voicecommands.IVoicedCommandHandler;
import l2f.gameserver.model.Player;

public class AutoFarm implements IVoicedCommandHandler
{
	private static final String[] COMMANDS =
	{
		"autofarm"
	};

	@Override
	public boolean useVoicedCommand(String command, Player activeChar, String target)
	{
		if (activeChar == null)
			return false;

		AutoFarmCommunity menu = new AutoFarmCommunity();
		menu.self = activeChar;
		menu.back();

		return true;
	}

	@Override
	public String[] getVoicedCommandList()
	{
		return COMMANDS;
	}
}