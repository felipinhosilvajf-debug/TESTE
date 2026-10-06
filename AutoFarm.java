package l2f.gameserver.handler.voicecommands.impl;

import l2f.gameserver.autofarm.AutoFarmCommunity;
import l2f.gameserver.handler.voicecommands.IVoicedCommandHandler;
import l2f.gameserver.model.Player;

public class AutoFarm implements IVoicedCommandHandler
{
	private static final String[] VOICED_COMMANDS = { "autofarm" };

	@Override
	public boolean useVoicedCommand(String command, Player activeChar, String target)
	{
		if (activeChar == null)
			return false;

		if (target != null && !target.isEmpty())
		{
			String action = target.trim();
			AutoFarmCommunity farm = new AutoFarmCommunity();
			farm.self = activeChar;

			// ATIVAÇÃO EXPLÍCITA SEGURA SEM CONDIÇÃO DE CORRIDA
			if (action.equalsIgnoreCase("toggle_on"))
			{
				if (!activeChar.isAutoFarm())
				{
					// Força os slots vazios a virarem 0 limpo no banco de dados
					activeChar.setAutoFarmSkills(0, 0, 0);
					farm.start();
					activeChar.sendMessage("Auto Farm: ATIVADO.");
				}
				return true;
			}
			else if (action.equalsIgnoreCase("toggle_off"))
			{
				if (activeChar.isAutoFarm())
				{
					farm.stop();
					activeChar.sendMessage("Auto Farm: DESATIVADO.");
				}
				return true;
			}
			else if (action.startsWith("select"))
			{
				String[] args = action.split(" ");
				if (args.length >= 3)
				{
					String[] selectArgs = new String[] { args[1], args[2] };
					farm.select(selectArgs);
				}
				return true;
			}
		}

		return true;
	}

	@Override
	public String[] getVoicedCommandList()
	{
		return VOICED_COMMANDS;
	}
}
