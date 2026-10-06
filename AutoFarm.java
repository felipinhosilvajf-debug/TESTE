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

			// Comando de Ligar/Desligar
			if (action.equalsIgnoreCase("toggle") || action.contains("toggle"))
			{
				if (activeChar.isAutoFarm())
				{
					farm.stop();
					activeChar.sendMessage("Auto Farm: DESATIVADO.");
				}
				else
				{
					int s1 = activeChar.getAutoFarmSkill1() < 0 ? 0 : activeChar.getAutoFarmSkill1();
					int s2 = activeChar.getAutoFarmSkill2() < 0 ? 0 : activeChar.getAutoFarmSkill2();
					int s3 = activeChar.getAutoFarmSkill3() < 0 ? 0 : activeChar.getAutoFarmSkill3();
					activeChar.setAutoFarmSkills(s1, s2, s3);

					farm.start();
					activeChar.sendMessage("Auto Farm: ATIVADO.");
				}
				return true;
			}
			// Comando de Salvar/Remover Habilidade (Isolado para evitar conflito de flood)
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
