package l2f.gameserver.autofarm;


import l2f.gameserver.data.htm.HtmCache;
import l2f.gameserver.model.Player;
import l2f.gameserver.model.Skill;
import l2f.gameserver.network.serverpackets.NpcHtmlMessage;

public class AutoFarmCommunity
{
	public Player self;

	private void showHtml(String html)
	{
		if (self == null)
			return;

		/*
		 * Auto Farm usa uma janela HTML pequena, no mesmo estilo
		 * de uma janela de NPC. Não abrir no Community Board.
		 */
		NpcHtmlMessage message =
			new NpcHtmlMessage(0);

		message.setHtml(html);

		self.sendPacket(message);
	}

	/*
	 * =============================================================
	 * AUTO FARM
	 * =============================================================
	 */

	public void start()
	{
		if (self == null)
			return;

		if (self.isAutoFarm())
		{
			back();
			return;
		}

		self.startAutoFarm();

		back();
	}

	public void stop()
	{
		if (self == null)
			return;

		if (!self.isAutoFarm())
		{
			back();
			return;
		}

		self.stopAutoFarm();

		back();
	}

	public void toggle()
	{
		if (self == null)
			return;

		if (self.isAutoFarm())
			stop();
		else
			start();
	}

	/*
	 * =============================================================
	 * POÇÕES
	 * =============================================================
	 */

	public void potion()
	{
		if (self == null)
			return;

		StringBuilder html =
			new StringBuilder();

		html.append("<html noscrollbar>");
		html.append("<title>Auto Farm - Recurso</title>");
		html.append("<body>");
		html.append("<center>");

		html.append(
			"<table width=210 border=0 cellpadding=0 cellspacing=0>"
		);

		html.append("<tr>");
		html.append("<td height=38 align=center>");

		html.append(
			"<font name=\"hs12\" color=\"LEVEL\">"
		);

		html.append("Recursos");

		html.append("</font>");
		html.append("</td>");
		html.append("</tr>");

		html.append("</table>");

		html.append("<br>");

		/*
		 * HP
		 */
		if (self.getInventory().getItemByItemId(1539) != null)
		{
			html.append(
				"<button value=\"HP - Greater Healing Potion\""
			);

			html.append(
				" action=\"bypass _bbsscripts;"
			);

			html.append(
				"l2f.gameserver.autofarm.AutoFarmCommunity:setPotion 1539\""
			);

			html.append(
				" width=330 height=28"
			);

			html.append(
				" back=\"L2UI_CH3.Btn_BF_Down\""
			);

			html.append(
				" fore=\"L2UI_CH3.Btn_BF\">"
			);

			html.append("<br>");
		}

		/*
		 * CP
		 */
		if (self.getInventory().getItemByItemId(5592) != null)
		{
			html.append(
				"<button value=\"CP - CP Potion\""
			);

			html.append(
				" action=\"bypass _bbsscripts;"
			);

			html.append(
				"l2f.gameserver.autofarm.AutoFarmCommunity:setPotion 5592\""
			);

			html.append(
				" width=330 height=28"
			);

			html.append(
				" back=\"L2UI_CH3.Btn_BF_Down\""
			);

			html.append(
				" fore=\"L2UI_CH3.Btn_BF\">"
			);

			html.append("<br>");
		}

		/*
		 * MP
		 */
		if (self.getInventory().getItemByItemId(728) != null)
		{
			html.append(
				"<button value=\"MP - Greater Mana Potion\""
			);

			html.append(
				" action=\"bypass _bbsscripts;"
			);

			html.append(
				"l2f.gameserver.autofarm.AutoFarmCommunity:setPotion 728\""
			);

			html.append(
				" width=330 height=28"
			);

			html.append(
				" back=\"L2UI_CH3.Btn_BF_Down\""
			);

			html.append(
				" fore=\"L2UI_CH3.Btn_BF\">"
			);

			html.append("<br>");
		}

		/*
		 * SEM POÇÃO
		 */
		html.append(
			"<button value=\"SEM POÇÃO\""
		);

		html.append(
			" action=\"bypass _bbsscripts;"
		);

		html.append(
			"l2f.gameserver.autofarm.AutoFarmCommunity:setPotion 0\""
		);

		html.append(
			" width=330 height=28"
		);

		html.append(
			" back=\"L2UI_CH3.Btn_BF_Down\""
		);

		html.append(
			" fore=\"L2UI_CH3.Btn_BF\">"
		);

		html.append("<br><br>");

		html.append(
			"<font color=\"AAAAAA\">"
		);

		html.append(
			"Recurso usado a 60%."
		);

		html.append("</font>");

		html.append("<br><br>");

		html.append(
			"<button value=\"VOLTAR\""
		);

		html.append(
			" action=\"bypass _bbsscripts;"
		);

		html.append(
			"l2f.gameserver.autofarm.AutoFarmCommunity:back\""
		);

		html.append(
			" width=100 height=27"
		);

		html.append(
			" back=\"L2UI_CH3.Btn_BF_Down\""
		);

		html.append(
			" fore=\"L2UI_CH3.Btn_BF\">"
		);

		html.append("</center>");
		html.append("</body>");
		html.append("</html>");

		showHtml(html.toString());
	}

	public void setPotion(String[] args)
	{
		if (self == null)
			return;

		if (args == null || args.length < 1)
			return;

		try
		{
			int itemId =
				Integer.parseInt(args[0]);

			/*
			 * Somente:
			 *
			 * 1539 HP
			 * 5592 CP
			 * 728 MP
			 * 0 Sem poção
			 */
			if (itemId != 0 &&
				itemId != 1539 &&
				itemId != 5592 &&
				itemId != 728)
			{
				self.sendMessage(
					"Recurso inválido."
				);

				return;
			}

			if (itemId > 0 &&
				self.getInventory().getItemByItemId(itemId) == null)
			{
				self.sendMessage(
					"Você não possui esta poção no inventário."
				);

				return;
			}

			self.setVar(
				"autofarm_potion",
				String.valueOf(itemId),
				-1
			);

			back();
		}
		catch (NumberFormatException e)
		{
			self.sendMessage(
				"Recurso inválido."
			);
		}
	}

	/*
	 * =============================================================
	 * RAIO
	 * =============================================================
	 */

	public void radius()
	{
		if (self == null)
			return;

		StringBuilder html =
			new StringBuilder();

		html.append("<html noscrollbar>");
		html.append("<title>Auto Farm - Raio</title>");
		html.append("<body>");
		html.append("<center>");

		html.append(
			"<table width=210 border=0 cellpadding=0 cellspacing=0>"
		);

		html.append("<tr>");
		html.append("<td height=38 align=center>");

		html.append(
			"<font name=\"hs12\" color=\"LEVEL\">"
		);

		html.append("Definir:");

		html.append("</font>");
		html.append("</td>");
		html.append("</tr>");

		html.append("</table>");

		html.append("<br>");

		int[] radii =
		{
			500,
			750,
			1000,
			1500,
			2000
		};

		for (int radius : radii)
		{
			html.append(
				"<button value=\""
			);

			html.append(radius);

			html.append(
				"\" action=\"bypass _bbsscripts;"
			);

			html.append(
				"l2f.gameserver.autofarm.AutoFarmCommunity:setRadius "
			);

			html.append(radius);

			html.append("\"");

			html.append(
				" width=180 height=27"
			);

			html.append(
				" back=\"L2UI_CH3.Btn_BF_Down\""
			);

			html.append(
				" fore=\"L2UI_CH3.Btn_BF\">"
			);

			html.append("<br>");
		}

		html.append("<br>");

		html.append(
			"<button value=\"VOLTAR\""
		);

		html.append(
			" action=\"bypass _bbsscripts;"
		);

		html.append(
			"l2f.gameserver.autofarm.AutoFarmCommunity:back\""
		);

		html.append(
			" width=100 height=27"
		);

		html.append(
			" back=\"L2UI_CH3.Btn_BF_Down\""
		);

		html.append(
			" fore=\"L2UI_CH3.Btn_BF\">"
		);

		html.append("</center>");
		html.append("</body>");
		html.append("</html>");

		showHtml(html.toString());
	}

	public void setRadius(String[] args)
	{
		if (self == null)
			return;

		if (args == null || args.length < 1)
			return;

		try
		{
			int radius =
				Integer.parseInt(args[0]);

			if (radius != 500 &&
				radius != 750 &&
				radius != 1000 &&
				radius != 1500 &&
				radius != 2000)
			{
				self.sendMessage(
					"Raio inválido."
				);

				return;
			}

			self.setAutoFarmRadius(radius);

			back();
		}
		catch (NumberFormatException e)
		{
			self.sendMessage(
				"Raio inválido."
			);
		}
	}

	/*
	 * =============================================================
	 * MENU PRINCIPAL
	 * =============================================================
	 */

	public int getShortcutSkillId(int slot)
	{
		if (self == null)
			return 0;

		try
		{
			if (self.getShortCut(slot, 9) == null)
				return 0;

			int skillId = self.getShortCut(slot, 9).getId();

			if (skillId <= 0)
				return 0;

			return isUsableAutoFarmSkill(skillId) ? skillId : 0;
		}
		catch (Exception e)
		{
			return 0;
		}
	}

	public void back()
	{
		if (self == null)
			return;

		/*
		 * Skills do Auto Farm vêm diretamente da barra de atalhos nativa.
		 * Página 9 / slots 0, 1 e 2 correspondem a S1, S2 e S3.
		 */
		int skill1 = getShortcutSkillId(0);
		int skill2 = getShortcutSkillId(1);
		int skill3 = getShortcutSkillId(2);
		String html =
			HtmCache.getInstance().getNotNull(
				"scripts/services/communityPVP/pages/AutoFarm.htm",
				self
			);




		html = html.replace(
			"%potion%",
			getPotionName(
				self.getVar("autofarm_potion")
			)
		);

		html = html.replace(
			"%radius%",
			String.valueOf(
				self.getAutoFarmRadius()
			)
		);

		html = html.replace(
			"%status%",
			self.isAutoFarm()
				? "ATIVADO"
				: "DESATIVADO"
		);

		showHtml(html);
	}

	private String getSkillIcon(int skillId)
	{
		if (skillId <= 0 || self == null)
			return "L2UI_CT1.Button_DF";

		Skill skill = self.getKnownSkill(skillId);

		if (skill == null || skill.getIcon() == null || skill.getIcon().isEmpty())
			return "L2UI_CT1.Button_DF";

		return skill.getIcon();
	}

	private String getSkillIcon(Skill skill)
	{
		if (skill == null || skill.getIcon() == null || skill.getIcon().isEmpty())
			return "L2UI_CT1.Button_DF";

		return skill.getIcon();
	}

	private boolean isUsableAutoFarmSkill(int skillId)
	{
		if (skillId <= 0 || self == null)
			return false;

		Skill skill = self.getKnownSkill(skillId);

		if (skill == null)
			return false;

		return skill.isActive() || skill.isToggle();
	}

	private String getSkillValue(int skillId)
	{
		return isUsableAutoFarmSkill(skillId) ? "" : "+";
	}

	private String getSkillName(int skillId)
	{
		if (skillId <= 0)
			return "Sem Skill";

		if (self == null)
			return "Sem Skill";

		Skill skill =
			self.getKnownSkill(skillId);

		if (skill == null)
			return "Sem Skill";

		String name = skill.getName();

		if (name == null ||
			name.isEmpty())
		{
			return "Sem Skill";
		}

		if (name.length() > 24)
			name = name.substring(0, 24);

		return name;
	}

	private String getPotionName(String varVal)
	{
		if (varVal == null ||
			varVal.isEmpty())
		{
			return "Sem Poção";
		}

		try
		{
			int itemId =
				Integer.parseInt(varVal);

			if (itemId == 1539)
				return "HP";

			if (itemId == 5592)
				return "CP";

			if (itemId == 728)
				return "MP";

			return "Sem Poção";
		}
		catch (NumberFormatException e)
		{
			return "Sem Poção";
		}
	}
}