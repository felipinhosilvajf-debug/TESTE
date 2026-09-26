package quests;

import java.util.ArrayList;
import java.util.Collection;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;
import java.util.StringTokenizer;

import l2f.gameserver.Config;
import l2f.gameserver.data.htm.HtmCache;
import l2f.gameserver.data.xml.holder.ItemHolder;
import l2f.gameserver.hwid.HwidGamer;
import l2f.gameserver.instancemanager.QuestManager;
import l2f.gameserver.listener.actor.OnCurrentHpDamageListener;
import l2f.gameserver.listener.actor.player.OnPlayerEnterListener;
import l2f.gameserver.model.GameObjectsStorage;
import l2f.gameserver.model.Player;
import l2f.gameserver.model.actor.instances.player.ShortCut;
import l2f.gameserver.model.actor.listener.CharListenerList;
import l2f.gameserver.model.base.ClassId;
import l2f.gameserver.model.base.Race;
import l2f.gameserver.model.entity.ChangeLogManager;
import l2f.gameserver.network.serverpackets.NpcHtmlMessage;
import l2f.gameserver.model.entity.CCPHelpers.CCPSecondaryPassword;
import l2f.gameserver.model.instances.NpcInstance;
import l2f.gameserver.model.instances.SchemeBufferInstance;
import l2f.gameserver.model.items.ItemInstance;
import l2f.gameserver.model.mail.Mail;
import l2f.gameserver.model.quest.Quest;
import l2f.gameserver.model.quest.QuestState;
import l2f.gameserver.network.serverpackets.Say2;
import l2f.gameserver.network.serverpackets.SystemMessage2;
import l2f.gameserver.network.serverpackets.components.ChatType;
import l2f.gameserver.network.serverpackets.components.SystemMsg;
import l2f.gameserver.network.serverpackets.ExShowScreenMessage;
import l2f.gameserver.network.serverpackets.MagicSkillUse;
import l2f.gameserver.network.serverpackets.RadarControl;
import l2f.gameserver.network.serverpackets.ShortCutRegister;
import l2f.gameserver.network.serverpackets.SocialAction;
import l2f.gameserver.network.serverpackets.SystemMessage2;
import l2f.gameserver.network.serverpackets.TutorialShowHtml;
import l2f.gameserver.network.serverpackets.components.SystemMsg;
import l2f.gameserver.scripts.ScriptFile;
import l2f.gameserver.templates.item.ItemTemplate;
import l2f.gameserver.templates.item.ItemTemplate.Grade;
import l2f.gameserver.templates.item.WeaponTemplate;
import l2f.gameserver.templates.item.WeaponTemplate.WeaponType;
import l2f.gameserver.utils.ItemFunctions;
import l2f.gameserver.utils.Util;


public class _255_Tutorial extends Quest implements ScriptFile, OnPlayerEnterListener
{
	// table for Quest Timer ( Ex == -2 ) [raceId, voice, html]
	public final String[][] QTEXMTWO = {
			{
					"0",
					"tutorial_voice_001a",
					"tutorial_human_fighter001.htm"
			},
			{
					"10",
					"tutorial_voice_001b",
					"tutorial_human_mage001.htm"
			},
			{
					"18",
					"tutorial_voice_001c",
					"tutorial_elven_fighter001.htm"
			},
			{
					"25",
					"tutorial_voice_001d",
					"tutorial_elven_mage001.htm"
			},
			{
					"31",
					"tutorial_voice_001e",
					"tutorial_delf_fighter001.htm"
			},
			{
					"38",
					"tutorial_voice_001f",
					"tutorial_delf_mage001.htm"
			},
			{
					"44",
					"tutorial_voice_001g",
					"tutorial_orc_fighter001.htm"
			},
			{
					"49",
					"tutorial_voice_001h",
					"tutorial_orc_mage001.htm"
			},
			{
					"53",
					"tutorial_voice_001i",
					"tutorial_dwarven_fighter001.htm"
			},
			{
					"123",
					"tutorial_voice_001k",
					"tutorial_kamael_male001.htm"
			},
			{
					"124",
					"tutorial_voice_001j",
					"tutorial_kamael_female001.htm"
			}
	};

	// table for Client Event Enable (8) [raceId, html, x, y, z]
	public final String[][] CEEa = {
			{
					"0",
					"tutorial_human_fighter007.htm",
					"-71424",
					"258336",
					"-3109"
			},
			{
					"10",
					"tutorial_human_mage007.htm",
					"-91036",
					"248044",
					"-3568"
			},
			{
					"18",
					"tutorial_elf007.htm",
					"46112",
					"41200",
					"-3504"
			},
			{
					"25",
					"tutorial_elf007.htm",
					"46112",
					"41200",
					"-3504"
			},
			{
					"31",
					"tutorial_delf007.htm",
					"28384",
					"11056",
					"-4233"
			},
			{
					"38",
					"tutorial_delf007.htm",
					"28384",
					"11056",
					"-4233"
			},
			{
					"44",
					"tutorial_orc007.htm",
					"-56736",
					"-113680",
					"-672"
			},
			{
					"49",
					"tutorial_orc007.htm",
					"-56736",
					"-113680",
					"-672"
			},
			{
					"53",
					"tutorial_dwarven_fighter007.htm",
					"108567",
					"-173994",
					"-406"
			},
			{
					"123",
					"tutorial_kamael007.htm",
					"-125872",
					"38016",
					"1251"
			},
			{
					"124",
					"tutorial_kamael007.htm",
					"-125872",
					"38016",
					"1251"
			}
	};

	// table for Question Mark Clicked (9 & 11) learning skills [raceId, html, x, y, z]
	public final String[][] QMCa = {
			{
					"0",
					"tutorial_fighter017.htm",
					"-83165",
					"242711",
					"-3720"
			},
			{
					"10",
					"tutorial_mage017.htm",
					"-85247",
					"244718",
					"-3720"
			},
			{
					"18",
					"tutorial_fighter017.htm",
					"45610",
					"52206",
					"-2792"
			},
			{
					"25",
					"tutorial_mage017.htm",
					"45610",
					"52206",
					"-2792"
			},
			{
					"31",
					"tutorial_fighter017.htm",
					"10344",
					"14445",
					"-4242"
			},
			{
					"38",
					"tutorial_mage017.htm",
					"10344",
					"14445",
					"-4242"
			},
			{
					"44",
					"tutorial_fighter017.htm",
					"-46324",
					"-114384",
					"-200"
			},
			{
					"49",
					"tutorial_fighter017.htm",
					"-46305",
					"-112763",
					"-200"
			},
			{
					"53",
					"tutorial_fighter017.htm",
					"115447",
					"-182672",
					"-1440"
			},
			{
					"123",
					"tutorial_fighter017.htm",
					"-118132",
					"42788",
					"723"
			},
			{
					"124",
					"tutorial_fighter017.htm",
					"-118132",
					"42788",
					"723"
			}
	};

	// table for Question Mark Clicked (24) newbie lvl [raceId, html]
	public final Map<Integer, String> QMCb = new HashMap<Integer, String>();

	// table for Question Mark Clicked (35) 1st class transfer [raceId, html]
	public final Map<Integer, String> QMCc = new HashMap<Integer, String>();

	// table for Tutorial Close Link (26) 2nd class transfer [raceId, html]
	public final Map<Integer, String> TCLa = new HashMap<Integer, String>();

	// table for Tutorial Close Link (23) 2nd class transfer [raceId, html]
	public final Map<Integer, String> TCLb = new HashMap<Integer, String>();

	// table for Tutorial Close Link (24) 2nd class transfer [raceId, html]
	public final Map<Integer, String> TCLc = new HashMap<Integer, String>();

	//private static TutorialShowListener _tutorialShowListener;

	@Override
	public void onLoad()
	{
	}

	@Override
	public void onReload()
	{
	}

	@Override
	public void onShutdown()
	{
	}

	public _255_Tutorial()
	{
		super(false);

		CharListenerList.addGlobal(this);

		//_tutorialShowListener = new TutorialShowListener();

		QMCb.put(0, "tutorial_human009.htm");
		QMCb.put(10, "tutorial_human009.htm");
		QMCb.put(18, "tutorial_elf009.htm");
		QMCb.put(25, "tutorial_elf009.htm");
		QMCb.put(31, "tutorial_delf009.htm");
		QMCb.put(38, "tutorial_delf009.htm");
		QMCb.put(44, "tutorial_orc009.htm");
		QMCb.put(49, "tutorial_orc009.htm");
		QMCb.put(53, "tutorial_dwarven009.htm");
		QMCb.put(123, "tutorial_kamael009.htm");
		QMCb.put(124, "tutorial_kamael009.htm");

		QMCc.put(0, "tutorial_21.htm");
		QMCc.put(10, "tutorial_21a.htm");
		QMCc.put(18, "tutorial_21b.htm");
		QMCc.put(25, "tutorial_21c.htm");
		QMCc.put(31, "tutorial_21g.htm");
		QMCc.put(38, "tutorial_21h.htm");
		QMCc.put(44, "tutorial_21d.htm");
		QMCc.put(49, "tutorial_21e.htm");
		QMCc.put(53, "tutorial_21f.htm");

		TCLa.put(1, "tutorial_22w.htm");
		TCLa.put(4, "tutorial_22.htm");
		TCLa.put(7, "tutorial_22b.htm");
		TCLa.put(11, "tutorial_22c.htm");
		TCLa.put(15, "tutorial_22d.htm");
		TCLa.put(19, "tutorial_22e.htm");
		TCLa.put(22, "tutorial_22f.htm");
		TCLa.put(26, "tutorial_22g.htm");
		TCLa.put(29, "tutorial_22h.htm");
		TCLa.put(32, "tutorial_22n.htm");
		TCLa.put(35, "tutorial_22o.htm");
		TCLa.put(39, "tutorial_22p.htm");
		TCLa.put(42, "tutorial_22q.htm");
		TCLa.put(45, "tutorial_22i.htm");
		TCLa.put(47, "tutorial_22j.htm");
		TCLa.put(50, "tutorial_22k.htm");
		TCLa.put(54, "tutorial_22l.htm");
		TCLa.put(56, "tutorial_22m.htm");

		TCLb.put(4, "tutorial_22aa.htm");
		TCLb.put(7, "tutorial_22ba.htm");
		TCLb.put(11, "tutorial_22ca.htm");
		TCLb.put(15, "tutorial_22da.htm");
		TCLb.put(19, "tutorial_22ea.htm");
		TCLb.put(22, "tutorial_22fa.htm");
		TCLb.put(26, "tutorial_22ga.htm");
		TCLb.put(32, "tutorial_22na.htm");
		TCLb.put(35, "tutorial_22oa.htm");
		TCLb.put(39, "tutorial_22pa.htm");
		TCLb.put(50, "tutorial_22ka.htm");

		TCLc.put(4, "tutorial_22ab.htm");
		TCLc.put(7, "tutorial_22bb.htm");
		TCLc.put(11, "tutorial_22cb.htm");
		TCLc.put(15, "tutorial_22db.htm");
		TCLc.put(19, "tutorial_22eb.htm");
		TCLc.put(22, "tutorial_22fb.htm");
		TCLc.put(26, "tutorial_22gb.htm");
		TCLc.put(32, "tutorial_22nb.htm");
		TCLc.put(35, "tutorial_22ob.htm");
		TCLc.put(39, "tutorial_22pb.htm");
		TCLc.put(50, "tutorial_22kb.htm");
	}

	@Override
	public String onEvent(String event, QuestState st, NpcInstance npc)
	{
		Player player = st.getPlayer();
		if (player == null)
			return null;

		String html = "";

		// Intercepta os eventos de login e navegação das 3 páginas individuais
		if (event.equals("CheckPass") || event.equals("ProposePass") || event.equals("UC"))
		{
			String text = HtmCache.getInstance().getNotNull("SpecialTutorial/VBtuto1.htm", player);
			st.showTutorialHTML(text);
			return null;
		}
		else if (event.equalsIgnoreCase("pagina2") || event.equalsIgnoreCase("255_Tutorial:pagina2"))
		{
			String text = HtmCache.getInstance().getNotNull("SpecialTutorial/VBtuto2.htm", player);
			st.showTutorialHTML(text);
			return null;
		}
		else if (event.equalsIgnoreCase("pagina3") || event.equalsIgnoreCase("255_Tutorial:pagina3"))
		{
			String text = HtmCache.getInstance().getNotNull("SpecialTutorial/VBtuto3.htm", player);
			st.showTutorialHTML(text);
			return null;
		}
		else if (event.equalsIgnoreCase("fechar_tutorial") || event.equalsIgnoreCase("CloseTutorial") || event.equalsIgnoreCase("255_Tutorial:fechar_tutorial"))
		{
			player.broadcastPacket(new SocialAction(player.getObjectId(), SocialAction.VICTORY));
			st.closeTutorial();
			return null;
		}
		else if (event.equals("onTutorialClose"))
		{
			onTutorialClose(st);
			return null;
		}
		else if (event.startsWith("TE"))
		{
			st.cancelQuestTimer("TE");
			int event_id = 0;
			if (!event.equalsIgnoreCase("TE"))
				event_id = Integer.valueOf(event.substring(2));
			if (event_id == 0)
				st.closeTutorial();
			else if (event_id == 49)
			{
				st.closeTutorial();
				return null;
			}
			else if (event_id == 50) //New Secondary Password
			{
				CCPSecondaryPassword.startSecondaryPasswordSetup(player, "secondaryPassF");
				st.closeTutorial();
				return null;
			}
		}

		// Client Event
		else if (event.startsWith("CE"))
		{
			// Tutorial events are ignored for subclasses.
			if (player.getActiveClassId() != player.getBaseClassId())
			{
				return null;
			}

			int event_id = Integer.valueOf(event.substring(2));

			// Level up event
			if (event_id == 40)
			{
			/*
			* =========================================================
			* RECOMPENSAS DE LEVEL
			* =========================================================
			*
			* Cada nível é tratado independentemente.
			*
			* Isso evita que uma recompensa perdida bloqueie
			* as recompensas seguintes.
			*
			* Também permite recuperar recompensas de personagens
			* que já passaram daquele nível.
			*/

			if (player.getClassId().level() == 0)
			{
				/*
				* =====================================================
				* NÍVEL 6
				* =====================================================
				*
				* Para personagens novos:
				* lvl < 6 significa que a recompensa ainda não passou
				* pelo estágio 6.
				*
				* Para personagens antigos que já possuem lvl >= 6,
				* consideramos que a recompensa já foi entregue.
				*/

				if (player.getLevel() >= 6 &&
					player.getVarInt("lvl") < 6)
				{
					player.setVar("lvl", "6");

					final ItemInstance adena =
						ItemFunctions.createItem(57);

					adena.setCount(10000);

					player.getInventory().addItem(
						adena,
						"SpecialTutorial"
					);

					st.showTutorialHTML(
						HtmCache.getInstance().getNotNull(
							"SpecialTutorial/boostlvl6.htm",
							player
						)
					);
				}

				/*
				* =====================================================
				* NÍVEL 7
				* =====================================================
				*
				* Se o jogador estiver no 7+ e ainda estiver marcado
				* abaixo do 7, recebe a recompensa.
				*/

				if (player.getLevel() >= 7 &&
					player.getVarInt("lvl") < 7)
				{
					player.setVar("lvl", "7");

					final ItemInstance adena =
						ItemFunctions.createItem(57);

					adena.setCount(10000);

					player.getInventory().addItem(
						adena,
						"SpecialTutorial"
					);

					st.showTutorialHTML(
						HtmCache.getInstance().getNotNull(
							"SpecialTutorial/boostlvl7.htm",
							player
						)
					);
				}

				/*
				* =====================================================
				* NÍVEL 8
				* =====================================================
				*/

				if (player.getLevel() >= 8 &&
					player.getVarInt("lvl") < 8)
				{
					player.setVar("lvl", "8");

					final ItemInstance adena =
						ItemFunctions.createItem(57);

					adena.setCount(10000);

					player.getInventory().addItem(
						adena,
						"SpecialTutorial"
					);

					st.showTutorialHTML(
						HtmCache.getInstance().getNotNull(
							"SpecialTutorial/boostlvl8.htm",
							player
						)
					);
				}

				/*
				* =====================================================
				* NÍVEL 9
				* =====================================================
				*/

				if (player.getLevel() >= 8 &&
					player.getVarInt("lvl") < 9)
				{
					player.setVar("lvl", "9");

					final ItemInstance adena =
						ItemFunctions.createItem(57);

					adena.setCount(10000);

					player.getInventory().addItem(
						adena,
						"SpecialTutorial"
					);

					st.showTutorialHTML(
						HtmCache.getInstance().getNotNull(
							"SpecialTutorial/boostlvl9.htm",
							player
						)
					);
				}

				/*
				* =====================================================
				* NÍVEL 10
				* =====================================================
				*/

				if (player.getLevel() >= 10 &&
					player.getVarInt("lvl") < 10)
				{
					player.setVar("lvl", "10");

					final ItemInstance adena =
						ItemFunctions.createItem(57);

					adena.setCount(10000);

					player.getInventory().addItem(
						adena,
						"SpecialTutorial"
					);

					final ItemInstance novoItem =
						ItemFunctions.createItem(13121);

					novoItem.setCount(1);

					player.getInventory().addItem(
						novoItem,
						"SpecialTutorial"
					);

					st.showTutorialHTML(
						HtmCache.getInstance().getNotNull(
							"SpecialTutorial/boostlvl10.htm",
							player
						)
					);
				}

				/*
				* =====================================================
				* NÍVEL 21
				* =====================================================
				*
				* IMPORTANTE:
				*
				* Não usamos mais firstexp == 5 para liberar a
				* recompensa.
				*
				* Assim, mesmo que o jogador tenha perdido alguma
				* recompensa anterior, ele continua podendo receber
				* a recompensa do nível 21.
				*/

				if (player.getLevel() >= 21 &&
					player.getVarInt("level21reward") < 1)
				{
					player.setVar(
						"level21reward",
						"1"
					);

					final ItemInstance adena =
						ItemFunctions.createItem(57);

					adena.setCount(30000);

					player.getInventory().addItem(
						adena,
						"SpecialTutorial"
					);

					checkClassMaster(st);
				}

			}

			/*
			* =====================================================
			* NÍVEL 40
				* =====================================================
				*
				* Novo tutorial de recompensa. Não faz mudança de classe.
				*/
				if (player.getLevel() >= 40 &&
					player.getVarInt("level40reward") < 1)
				{
					player.setVar("level40reward", "1");
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level40.htm", player));
				}

				/*
				* =====================================================
				* NÍVEL 52
				* =====================================================
				*
				* Novo tutorial de recompensa. Não faz mudança de classe.
				*/
				if (player.getLevel() >= 52 &&
					player.getVarInt("level52reward") < 1)
				{
					player.setVar("level52reward", "1");
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level52.htm", player));
				}

			}
			// Exp events
			else if (event_id == 41)
			{
				// Synerge - When getting the first exp the tutorial should close
				if (st.getInt("firstexp") < 1)
				{
					st.set("firstexp", "1");
					st.closeTutorial();
				}
			}
		}
		// Mudança de classe pelo Class Master
		else if (event.startsWith("ChangeTo"))
		{
			StringTokenizer tokenizer = new StringTokenizer(event, ";");

			tokenizer.nextToken();

			final int newClassId = Integer.parseInt(tokenizer.nextToken());
			final long price = Long.parseLong(tokenizer.nextToken());

			// Proteção contra preço inválido/manipulação do bypass.
			if (price < 0L)
			{
				st.closeTutorial();
				return null;
			}

			// A classe escolhida precisa pertencer à árvore da classe atual.
			if (!ClassId.VALUES[newClassId].equalsOrChildOf(ClassId.VALUES[player.getActiveClassId()]))
			{
				st.closeTutorial();
				return null;
			}

			final int jobLevel = player.getClassId().getLevel();

			// Verifica se o personagem realmente pode fazer essa mudança.
			if (!canChangeClass(player, jobLevel))
			{
				st.closeTutorial();
				return null;
			}

			final ItemTemplate item = ItemHolder.getInstance().getTemplate(Config.CLASS_MASTERS_PRICE_ITEM);
			final ItemInstance pay = player.getInventory().getItemByItemId(item.getItemId());

			if (pay != null && pay.getCount() >= price)
			{
				// Cobra o valor configurado do Class Master.
				player.getInventory().destroyItem(pay, price, "_255_Tutorial");

				// Mensagem de conclusão da mudança de classe.
				if (jobLevel == 3)
				{
					player.sendPacket(SystemMsg.CONGRATULATIONS__YOUVE_COMPLETED_YOUR_THIRDCLASS_TRANSFER_QUEST);
				}
				else
				{
					player.sendPacket(SystemMsg.CONGRATULATIONS__YOUVE_COMPLETED_A_CLASS_TRANSFER);
				}

				// Faz a mudança REAL da classe.
				player.setClassId(newClassId, false, false);

				// Efeito visual da mudança de classe.
				final MagicSkillUse msu = new MagicSkillUse(player, player, 5103, 1, 1, 1);
				player.broadcastPacket(msu);

				// Atualiza o personagem para todos os jogadores.
				player.broadcastUserInfo(true);

				// Fecha o Class Master.
				st.closeTutorial();

				// =====================================================
				// PRIMEIRA MUDANÇA DE CLASSE
				// =====================================================
				if (player.getLevel() < 70
						&& player.getActiveClassId() == player.getBaseClassId())
				{
					if (jobLevel == 1 && player.getVarInt("lvl") < 21)
					{
						// Agora sim o tutorial considera o jogador
						// oficialmente no estágio do nível 21.
						player.setVar("lvl", "21");

						// Abre o seu HTML traduzido/nova versão.
						player.sendPacket(
							new TutorialShowHtml(
								HtmCache.getInstance().getNotNull(
									"SpecialTutorial/Level21.htm",
									player
								)
							)
						);
					}
				}

				return null;
			}

			// Não possui Adena/item suficiente.
			if (Config.CLASS_MASTERS_PRICE_ITEM == ItemTemplate.ITEM_ID_ADENA)
			{
				player.sendPacket(new SystemMessage2(SystemMsg.YOU_DO_NOT_HAVE_ENOUGH_ADENA));
			}
			else
			{
				player.sendPacket(new SystemMessage2(SystemMsg.YOU_DO_NOT_HAVE_ENOUGH_ADENA));
			}

			st.closeTutorial();
			return null;
		}
		// Synerge - Gives the character a certain weapon id and equips it
		else if (event.startsWith("GetWeaponD "))
		{
			String weaponVar = "weapon";

			if (player.getVarInt("level52reward") == 1)
				weaponVar = "level52weapon";
			else if (player.getVarInt("level40reward") == 1)
				weaponVar = "level40weapon";

			if (player.getVarInt(weaponVar) >= 1)
				return null;

			StringTokenizer tokenizer = new StringTokenizer(event, " ");
			tokenizer.nextToken();
			final int itemId = Integer.parseInt(tokenizer.nextToken());

			final ItemInstance createditem = ItemFunctions.createItem(itemId);

			if (createditem == null || createditem.getCrystalType() != Grade.D)
			{
				player.sendMessage("Wrong weapon");
				st.closeTutorial();
				return null;
			}

			player.setVar(weaponVar, "1");
			player.getInventory().addItem(createditem, "SpecialTutorial");

			// Also give arrows if the weapon is a bow
			if (createditem.isWeapon() && ((WeaponTemplate)createditem.getTemplate()).getItemType() == WeaponType.BOW)
			{
				final ItemInstance arrows = ItemFunctions.createItem(1341);
				arrows.setCount(300);
				player.getInventory().addItem(arrows, "SpecialTutorial");
			}

			// Unequip the current player's weapon
			if (player.getActiveWeaponInstance() != null)
				player.getInventory().unEquipItem(player.getActiveWeaponInstance());

			// Equip the new item
			player.getInventory().equipItem(createditem);

			// Show the armor html belonging to the tutorial level that is currently being used.
			if (player.getVarInt("level52reward") == 1)
			{
				if (player.getRace() == Race.kamael)
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level52ArmorKamael.htm", player));
				else
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level52Armors.htm", player));
			}
			else if (player.getVarInt("level40reward") == 1)
			{
				if (player.getRace() == Race.kamael)
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level40ArmorKamael.htm", player));
				else
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level40Armors.htm", player));
			}
			else
			{
				if (player.getRace() == Race.kamael)
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level21ArmorKamael.htm", player));
				else
					st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level21Armors.htm", player));
			}
		}
		// Synerge - Gives the character a certain armor ids and equips it
		else if (event.startsWith("GetArmorD "))
		{
			String armorVar = "armor";

			if (player.getVarInt("level52reward") == 1)
				armorVar = "level52armor";
			else if (player.getVarInt("level40reward") == 1)
				armorVar = "level40armor";

			if (player.getVarInt(armorVar) >= 1)
				return null;

			StringTokenizer tokenizer = new StringTokenizer(event, " ");
			tokenizer.nextToken();

			player.setVar(armorVar, "1");

			// We have to give and equip each item that is sent through the bypass
			while (tokenizer.hasMoreTokens())
			{
				final int itemId = Integer.parseInt(tokenizer.nextToken());
				final ItemInstance createditem = ItemFunctions.createItem(itemId);

				if (createditem == null || createditem.getCrystalType() != Grade.D)
				{
					player.sendMessage("Wrong Armor");
					st.closeTutorial();
					return null;
				}

				player.getInventory().addItem(createditem, "SpecialTutorial");

				// Unequip the current player's armor slot
				player.getInventory().unEquipItemInBodySlot(createditem.getBodyPart());

				// Equip the new item
				player.getInventory().equipItem(createditem);
			}

			// The new level tutorials end after armor. Level 21 keeps its existing soulshot flow.
			if (player.getVarInt("level52reward") == 1 ||
				player.getVarInt("level40reward") == 1)
			{
				st.closeTutorial();
			}
			else
			{
				st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/Level21Soulshots.htm", player));
			}
		}
		// Synerge - Gives the character some shots
		else if (event.startsWith("GetShotsD ") && player.getVarInt("shots") < 1)
		{
			StringTokenizer tokenizer = new StringTokenizer(event, " ");
			tokenizer.nextToken();
			final int itemId = Integer.parseInt(tokenizer.nextToken());
			final int itemCount = Integer.parseInt(tokenizer.nextToken());

			final ItemInstance createditem = ItemFunctions.createItem(itemId);

			if (createditem == null || createditem.getCrystalType() != Grade.D)
			{
				player.sendMessage("Wrong shots");
				st.closeTutorial();
				return null;
			}

			createditem.setCount(itemCount);

			player.setVar("shots", "1");
			player.getInventory().addItem(createditem, "SpecialTutorial");

			// Add the soulshots to a new shortcut
			ShortCut shortCut = new ShortCut(11, 0, ShortCut.TYPE_ITEM, createditem.getObjectId(), -1, 1);
			player.sendPacket(new ShortCutRegister(player, shortCut));
			player.registerShortCut(shortCut);

		}
		// Synerge - Allows to open htmls directly as a bypass. Support for link on tutorials? Should work outside this, but whatever
		else if (event.startsWith("Link "))
		{
			StringTokenizer tokenizer = new StringTokenizer(event, " ");
			tokenizer.nextToken();
			final String htm = tokenizer.nextToken();

			st.showTutorialHTML(HtmCache.getInstance().getNotNull("SpecialTutorial/" + htm, player));
		}
		// Synerge - Shows a certain npc in the map and radar
		else if (event.startsWith("ShowLocation "))
		{
			StringTokenizer tokenizer = new StringTokenizer(event, " ");
			tokenizer.nextToken();
			final int npcId = Integer.parseInt(tokenizer.nextToken());

			final NpcInstance npcLoc = GameObjectsStorage.getByNpcId(npcId);
			if (npcLoc != null)
				player.sendPacket(new RadarControl(2, 2, npcLoc.getLoc()), new RadarControl(0, 1, npcLoc.getLoc()));

			st.closeTutorial();
		}

		if (html.isEmpty())
			return null;
		st.showTutorialPage(html);
		return null;
	}

	private static boolean checkCanSeeTutorial(Player player)
	{
		return !player.containsQuickVar("watchingTutorial");
	}

	private static void addToTutorialQueue(Player player, String pageToCheck)
	{
		@SuppressWarnings("unchecked")
		Collection<String> tutorialsToSee = (List<String>) player.getQuickVarO("tutorialsToSee", new ArrayList<String>());
		tutorialsToSee.add(pageToCheck);
		if (!player.containsQuickVar("tutorialsToSee"))
		{
			player.addQuickVar("tutorialsToSee", tutorialsToSee);
		}
	}

	private static void onTutorialClose(QuestState st)
	{
		Player player = st.getPlayer();
		if (player.containsQuickVar("tutorialsToSee"))
		{
			@SuppressWarnings("unchecked")
			List<String> tutorialsToSee = (List<String>) player.getQuickVarO("tutorialsToSee", new ArrayList<String>());
			String tutorialToSee = tutorialsToSee.remove(0);
			if (tutorialsToSee.isEmpty())
				player.deleteQuickVar("tutorialsToSee");
			switch (tutorialToSee)
			{
				case "checkChangeLog":
					checkChangeLog(st);
					return;
				case "checkClassMaster":
					checkClassMaster(st);
					return;
				default:
			}
		}
	}

	private static void checkChangeLog(QuestState st)
	{
		Player player = st.getPlayer();
		if (!checkCanSeeTutorial(player))
		{
			addToTutorialQueue(player, "checkChangeLog");
		}
		else
		{
			int lastNotSeenChange = ChangeLogManager.getInstance().getNotSeenChangeLog(player);
			if (lastNotSeenChange >= 0)
			{
				String change = ChangeLogManager.getInstance().getChangeLog(lastNotSeenChange);
				st.showTutorialHTML(change);
				HwidGamer gamer = player.getHwidGamer();
				if (gamer != null)
					gamer.setSeenChangeLog(ChangeLogManager.getInstance().getLatestChangeId(), true);
			}
		}
	}

	/**
	 * If {@link #canChangeClass(l2f.gameserver.model.Player, int) canChangeClass}, showing Tutorial Page with next Classes that player can advance to
	 * @param st
	 */
	private static void checkClassMaster(QuestState st)
	{
		Player player = st.getPlayer();

		if (!checkCanSeeTutorial(player))
		{
			addToTutorialQueue(player, "OpenClassMaster");
			return;
		}

		ClassId classId = player.getClassId();
		int jobLevel = classId.getLevel();

		if (Config.ALLOW_CLASS_MASTERS_LIST.isEmpty() || !Config.ALLOW_CLASS_MASTERS_LIST.contains(jobLevel))
			jobLevel = 4;

		if (canChangeClass(player, jobLevel))
		{
			StringBuilder html = new StringBuilder();
			html.append("<html noscrollbar><head><title>Virtual Boost Tutorial</title></head>");
			html.append("<body>");
			html.append("<table border=0 cellpadding=0 cellspacing=0 width=292 height=310 background=\"l2ui_ct1.Windows_DF_TooltipBG\">");
			html.append("<tr><td align=center><br>");
			html.append("<table width=280><tr><td align=center valign=center>");
			html.append("<img src=\"L2UI.squaregray\" width=285 height=1/> ");
			html.append("<font name=hs12 color=3399FF>VirtualBoost</font>");
			html.append("<img src=\"L2UI.squaregray\" width=285 height=1/>");
			html.append("</td></tr></table>");
			html.append("<br></td></tr><tr>");
			html.append("<td align=center height=150>");
			html.append("<table width=280><tr><td align=center><img src=\"tut.logo2\" width=240 height=64></td></tr>");
			html.append("<tr><td align=center><br><font color=LEVEL name=hs12>Bem Vindo - VirtualBoost ! </font></td></tr>");
			html.append("</table>");
			html.append("<br1>");
			html.append("<table width=280><tr><td align=center>");
			html.append("<font color=00ff99>").append(player.getName()).append("</font> mude sua classe <font color=\"LEVEL\">").append(Util.formatAdena(Config.CLASS_MASTERS_PRICE_LIST[jobLevel])).append(" Adena</font>!<br1>");
			html.append("</td></tr></table>");
			html.append("<table width=280>");
			for (ClassId cid : ClassId.values())
			{
				if (cid != ClassId.inspector && cid.childOf(classId) && cid.level() == classId.level() + 1)
				{
					String name = cid.name().substring(0, 1).toUpperCase() + cid.name().substring(1);
					html.append("<tr><td align=center><button value=\"").append(name).append("\" action=\"bypass -h ChangeTo;").append(cid.getId()).append(';').append(Config.CLASS_MASTERS_PRICE_LIST[jobLevel]).append("\" width=200 height=32 back=\"L2UI_CT1.OlympiadWnd_DF_HeroConfirm_Down\" fore=\"L2UI_CT1.OlympiadWnd_DF_HeroConfirm\"></td></tr>");
				}
			}
			html.append("<tr><td align=center><button value=\"Remind me later\" action=\"bypass CloseTutorial\" width=200 height=28 back=\"L2UI_CT1.OlympiadWnd_DF_Back_Down\" fore=\"L2UI_CT1.OlympiadWnd_DF_Back\"></td></tr>");
			html.append("</table>");
			html.append("</td></tr><tr>");
			html.append("<td align=center><table width=280><tr>");
			html.append("<td align=center valign=center>");
			html.append("<img src=\"L2UI.squaregray\" width=285 height=1/> ");
			html.append("<font name=hs12 color=3399FF>VirtualBoost</font>");
			html.append("<img src=\"L2UI.squaregray\" width=285 height=1/> ");
			html.append("</td></tr></table><br></td></tr>");
			html.append("</table></body></html>");

			st.closeTutorial(); // Close the tutorial first so the other html can be shown
			st.showTutorialHTML(html.toString());
		}
	}

	/**
	 * Checking if player have got level >= 20, >= 40 or >= 76 and still didn't change class
	 * @param player to check
	 * @param jobLevel level of the class
	 * @return can change class
	 */
	private static boolean canChangeClass(Player player, int jobLevel)
	{
		int level = player.getLevel();

		if (!Config.ALLOW_CLASS_MASTERS_LIST.contains(jobLevel))
			return false;
		if (level >= 20 && jobLevel == 1)
			return true;
		if (level >= 40 && jobLevel == 2)
			return true;
		if (level >= 76 && jobLevel == 3)
			return true;
		return false;
	}

	@Override
	public void onPlayerEnter(Player player)
	{
		/*
		if (player.getLevel() < 6)
			player.addListener(_tutorialShowListener);
			*/
	}

	/*
	public class TutorialShowListener implements OnCurrentHpDamageListener
	{
		@Override
		public void onCurrentHpDamage(Creature actor, double damage, Creature attacker, Skill skill)
		{
			Player player = actor.getPlayer();
			if (player.getCurrentHpPercents() < 25)
			{
				player.removeListener(_tutorialShowListener);
				Quest q = QuestManager.getQuest(255);
				if (q != null)
					player.processQuestEvent(q.getName(), "CE45", null);
			}
			else if (player.getLevel() > 5)
				player.removeListener(_tutorialShowListener);
		}
	}
	*/

	@Override
	public boolean isVisible()
	{
		return false;
	}
}