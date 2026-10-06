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

		// Trata o comando de ativação de forma isolada e robusta
		if (target != null && target.trim().equalsIgnoreCase("toggle"))
		{
			AutoFarmCommunity farm = new AutoFarmCommunity();
			farm.self = activeChar;

			if (activeChar.isAutoFarm())
			{
				farm.stop(); // Desliga a IA de farm do servidor
				activeChar.sendMessage("Auto Farm: DESATIVADO.");
			}
			else
			{
				/*
				 * SOLUÇÃO DE MEMÓRIA: Se as variáveis do personagem vierem zeradas ou nulas,
				 * nós forçamos elas a virarem 0 limpo antes de dar o start(). Isso remove
				 * o congelamento invisível da Thread e permite o início do bot em qualquer situação!
				 */
				int s1 = activeChar.getAutoFarmSkill1() < 0 ? 0 : activeChar.getAutoFarmSkill1();
				int s2 = activeChar.getAutoFarmSkill2() < 0 ? 0 : activeChar.getAutoFarmSkill2();
				int s3 = activeChar.getAutoFarmSkill3() < 0 ? 0 : activeChar.getAutoFarmSkill3();
				activeChar.setAutoFarmSkills(s1, s2, s3);

				farm.start(); // Liga a IA de farm do servidor
				activeChar.sendMessage("Auto Farm: ATIVADO.");
			}
			return true;
		}
		// Trata o salvamento de habilidades pelo Drag & Drop
		else if (target != null && target.trim().startsWith("select"))
		{
			String[] args = target.trim().split(" ");
			if (args.length >= 3)
			{
				AutoFarmCommunity farm = new AutoFarmCommunity();
				farm.self = activeChar;
				
				String[] selectArgs = new String[] { args[1], args[2] };
				farm.select(selectArgs);
			}
			return true;
		}

		return true;
	}


	@Override
	public String[] getVoicedCommandList()
	{
		return VOICED_COMMANDS;
	}
}
