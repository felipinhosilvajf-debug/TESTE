class AutoPotions extends UICommonAPI;

const TIMER_CP = 2221;
const TIMER_HP = 2222;
const TIMER_MP = 2223;
const TIMER_QHP = 2224;
const TIMER_SCP = 2225;

var WindowHandle Me;

var ItemWindowHandle hCP;	
var ItemWindowHandle hHP;	
var ItemWindowHandle hMP;	
var ItemWindowHandle hQHP;	
var ItemWindowHandle hSCP;
var ItemWindowHandle hS;
var ItemWindowHandle hRand1;
var ItemWindowHandle hRand2;
var ItemWindowHandle hRand3;

var ItemWindowHandle InvItem;

var EditBoxHandle ePercentCP;
var EditBoxHandle ePercentHP;
var EditBoxHandle ePercentMP;
var EditBoxHandle ePercentQHP;
var EditBoxHandle ePercentSCP;
var EditBoxHandle eDelayCP;
var EditBoxHandle eDelayHP;
var EditBoxHandle eDelayMP;
var EditBoxHandle eDelayQHP;
var EditBoxHandle eDelaySCP;
var EditBoxHandle eAmountS;

var ButtonHandle bExpand;
var ButtonHandle bExpandMore;

var TextureHandle tDivider;
var TextureHandle tDivider2;
var TextureHandle tBlankCP;
var TextureHandle tBlankHP;
var TextureHandle tBlankMP;
var TextureHandle tBlankQHP;
var TextureHandle tBlankSCP;
var TextureHandle tBlankS;
var TextureHandle tBlankRand1;
var TextureHandle tBlankRand2;
var TextureHandle tBlankRand3;

var TextBoxHandle txtPercentQHP;
var TextBoxHandle txtPercentSCP;
var TextBoxHandle txtDelayQHP;
var TextBoxHandle txtDelaySCP;
var TextBoxHandle txtDescQHP;
var TextBoxHandle txtDescSCP;
var TextBoxHandle txtDescS;
var TextBoxHandle txtDescRand1;
var TextBoxHandle txtDescRand2;
var TextBoxHandle txtDescRand3;

var AnimTextureHandle aTex1;
var AnimTextureHandle aTex2;
var AnimTextureHandle aTex3;
var AnimTextureHandle aTex4;
var AnimTextureHandle aTex5;
var AnimTextureHandle aTex6;
var AnimTextureHandle aTex7;
var AnimTextureHandle aTex8;
var AnimTextureHandle aTex9;

var bool useCP;
var bool useHP;
var bool useMP;
var bool useQHP;
var bool useSCP;
var bool useS;
var bool useRand1;
var bool useRand2;
var bool useRand3;

var bool isWWExist;
var bool isAcumExist;
var bool isHasteExist;

var ItemID idCP;
var ItemID idHP;
var ItemID idMP;
var ItemID idQHP;
var ItemID idSCP;
var ItemID idS;
var ItemID idRand1;
var ItemID idRand2;
var ItemID idRand3;

function OnRegisterEvent()
{
	RegisterEvent(EV_UpdateUserInfo);
	RegisterEvent(EV_GamingStateEnter);
	RegisterEvent(EV_AbnormalStatusEtcItem);
	RegisterEvent(EV_AbnormalStatusNormalItem);
}

function OnLoad()
{
	Me = GetWindowHandle("AutoPotions");
	
	//ItemWindows
	hCP = GetItemWindowHandle("AutoPotions.itemCP");
	hHP = GetItemWindowHandle("AutoPotions.itemHP");
	hMP = GetItemWindowHandle("AutoPotions.itemMP");
	hQHP = GetItemWindowHandle("AutoPotions.itemQHP");
	hSCP = GetItemWindowHandle("AutoPotions.itemSCP");
	hS = GetItemWindowHandle("AutoPotions.itemSouls");
	hRand1 = GetItemWindowHandle("AutoPotions.itemRand1");
	hRand2 = GetItemWindowHandle("AutoPotions.itemRand2");
	hRand3 = GetItemWindowHandle("AutoPotions.itemRand3");
	
	InvItem = GetItemWindowHandle( "InventoryWnd.InventoryItem" );
	
	//EditBoxes
	ePercentCP = GetEditBoxHandle("AutoPotions.percentCP");
	ePercentHP = GetEditBoxHandle("AutoPotions.percentHP");
	ePercentMP = GetEditBoxHandle("AutoPotions.percentMP");
	ePercentQHP = GetEditBoxHandle("AutoPotions.percentQHP");
	ePercentSCP = GetEditBoxHandle("AutoPotions.percentSCP");
	eDelayCP = GetEditBoxHandle("AutoPotions.delayCP");
	eDelayHP = GetEditBoxHandle("AutoPotions.delayHP");
	eDelayMP = GetEditBoxHandle("AutoPotions.delayMP");
	eDelayQHP = GetEditBoxHandle("AutoPotions.delayQHP");
	eDelaySCP = GetEditBoxHandle("AutoPotions.delaySCP");
	eAmountS = GetEditBoxHandle("AutoPotions.amountSouls");
	
	//Buttons
	bExpand = GetButtonHandle("AutoPotions.expandBtn");
	bExpandMore = GetButtonHandle("AutoPotions.expandMoreBtn");
	
	//Textures
	tDivider = GetTextureHandle("AutoPotions.divider0");
	tDivider2 = GetTextureHandle("AutoPotions.divider1");
	tBlankCP = GetTextureHandle("AutoPotions.texCP");
	tBlankHP = GetTextureHandle("AutoPotions.texHP");
	tBlankMP = GetTextureHandle("AutoPotions.texMP");
	tBlankQHP = GetTextureHandle("AutoPotions.texQHP");
	tBlankSCP = GetTextureHandle("AutoPotions.texSCP");
	tBlankS = GetTextureHandle("AutoPotions.texSouls");
	tBlankRand1 = GetTextureHandle("AutoPotions.texRand1");
	tBlankRand2 = GetTextureHandle("AutoPotions.texRand2");
	tBlankRand3 = GetTextureHandle("AutoPotions.texRand3");
	
	//TextBoxes
	txtPercentQHP = GetTextBoxHandle("AutoPotions.percentTextQHP");
	txtPercentSCP = GetTextBoxHandle("AutoPotions.percentTextSCP");
	txtDelayQHP = GetTextBoxHandle("AutoPotions.delayTextQHP");
	txtDelaySCP = GetTextBoxHandle("AutoPotions.delayTextSCP");
	txtDescQHP = GetTextBoxHandle("AutoPotions.descQHP");
	txtDescSCP = GetTextBoxHandle("AutoPotions.descSCP");
	txtDescS = GetTextBoxHandle("AutoPotions.descSouls");
	txtDescRand1 = GetTextBoxHandle("AutoPotions.descRand1");
	txtDescRand2 = GetTextBoxHandle("AutoPotions.descRand2");
	txtDescRand3 = GetTextBoxHandle("AutoPotions.descRand3");
	
	//AnimTextureBoxes
	aTex1 = GetAnimTextureHandle("AutoPotions.Anim1");
	aTex2 = GetAnimTextureHandle("AutoPotions.Anim2");
	aTex3 = GetAnimTextureHandle("AutoPotions.Anim3");
	aTex4 = GetAnimTextureHandle("AutoPotions.Anim4");
	aTex5 = GetAnimTextureHandle("AutoPotions.Anim5");
	aTex6 = GetAnimTextureHandle("AutoPotions.Anim6");
	aTex7 = GetAnimTextureHandle("AutoPotions.Anim7");
	aTex8 = GetAnimTextureHandle("AutoPotions.Anim8");
	aTex9 = GetAnimTextureHandle("AutoPotions.Anim9");
	
		//Load settings
	//InitLoadSets();
	SetToDefault(); // Tire as duas barras daqui para ela voltar a rodar
	
	// Força a janela a ter o tamanho correto desde o carregamento do jogo
	Me.SetWindowSize(215, 90);
}



function OnShow()
{
	// Força o tamanho compacto perfeito que esconde o restante do painel
	Me.SetWindowSize(215, 90);
	
	// Esconde as escritas que estavam flutuando na parte esticada
	txtDescQHP.HideWindow();
	txtDescSCP.HideWindow();
	txtDescS.HideWindow();
	txtDescRand1.HideWindow();
	txtDescRand2.HideWindow();
	txtDescRand3.HideWindow();
	
	txtPercentQHP.HideWindow();
	txtPercentSCP.HideWindow();
	txtDelayQHP.HideWindow();
	txtDelaySCP.HideWindow();
	
	// Garante a visibilidade correta da janela mestre
	Me.ShowWindow();
	Me.SetFocus();
}



function InitLoadSets()
{
	bExpand.SetTexture( "L2UI_CH3.ShortcutWnd.shortcut_minimize", "L2UI_CH3.ShortcutWnd.shortcut_minimize_down", "L2UI_CH3.ShortcutWnd.shortcut_minimize_over" );
	bExpandMore.SetTexture( "L2UI_CH3.ShortcutWnd.shortcut_minimize", "L2UI_CH3.ShortcutWnd.shortcut_minimize_down", "L2UI_CH3.ShortcutWnd.shortcut_minimize_over" );
	Me.SetWindowSize(215, 90);
	tDivider.HideWindow();
	tDivider2.HideWindow();
	hQHP.HideWindow();
	hSCP.HideWindow();
	hS.HideWindow();
	hRand1.HideWindow();
	hRand2.HideWindow();
	hRand3.HideWindow();
	ePercentQHP.HideWindow();
	ePercentSCP.HideWindow();
	eDelayQHP.HideWindow();
	eDelaySCP.HideWindow();
	eAmountS.HideWindow();
	txtPercentQHP.HideWindow();
	txtPercentSCP.HideWindow();
	txtDelayQHP.HideWindow();
	txtDelaySCP.HideWindow();
	txtDescQHP.HideWindow();
	txtDescSCP.HideWindow();
	txtDescS.HideWindow();
	txtDescRand1.HideWindow();
	txtDescRand2.HideWindow();
	txtDescRand3.HideWindow();
	
	ePercentCP.SetString( "" );
	ePercentHP.SetString( "" );
	ePercentMP.SetString( "" );
	ePercentQHP.SetString( "" );
	ePercentSCP.SetString( "" );
	eDelayCP.SetString( "" );
	eDelayHP.SetString( "" );
	eDelayMP.SetString( "" );
	eDelayQHP.SetString( "" );
	eDelaySCP.SetString( "" );
	eAmountS.SetString( "" );
	
	ePercentCP.SetMaxLength( 0 );
	ePercentHP.SetMaxLength( 0 );
	ePercentMP.SetMaxLength( 0 );
	ePercentQHP.SetMaxLength( 0 );
	ePercentSCP.SetMaxLength( 0 );
	
	eAmountS.SetMaxLength( 0 );
	
	eDelayCP.SetMaxLength( 0 );
	eDelayHP.SetMaxLength( 0 );
	eDelayMP.SetMaxLength( 0 );
	eDelayQHP.SetMaxLength( 0 );
	eDelaySCP.SetMaxLength( 0 );
}

function ItemWindowHandle GetItemHandleByString(string str)
{
	switch (str)
	{
		case "itemCP":
			return hCP;
		break;
		case "itemHP":
			return hHP;
		break;
		case "itemMP":
			return hMP;
		break;
		case "itemQHP":
			return hQHP;
		break;
		case "itemSCP":
			return hSCP;
		break;
		case "itemSouls":
			return hS;
		break;
	}
}

function bool isBuffPotion(ItemInfo a_ItemInfo)
{
	//if (a_ItemInfo.ItemType == 5 && a_ItemInfo.ItemType == 3)
	//{
		if (InStr( a_ItemInfo.Name, "Greater Haste Potion" ) > -1)
		{
			return true;
		}
		else if (InStr( a_ItemInfo.Name, "Greater Swift Attack Potion" ) > -1)
		{
			return true;
		}
		else if (InStr( a_ItemInfo.Name, "Greater Magic Haste Potion" ) > -1)
		{
			return true;
		}
		else if (InStr( a_ItemInfo.Name, "Instant Haste Potion" ) > -1)
		{
			return true;
		}
		else
		{
			return false;
		}
	//}
	//else
	//{
		//sysDebug("HMMM");
		//return false;
	//}
}

function OnDropItem( String a_WindowID, ItemInfo a_ItemInfo, int X, int Y)
{
	switch (a_WindowID)
	{
		case "itemCP":
			if (a_ItemInfo.ID.ClassID == 5592 || InStr( a_ItemInfo.Name, "Greater CP Potion" ) > -1)
			{
				idCP = a_ItemInfo.ID;
				hCP.AddItem(a_ItemInfo);
				eDelayCP.SetString( "1" );
				eDelayCP.SetMaxLength( 2 );
				ePercentCP.SetString( "99" );
				ePercentCP.SetMaxLength( 2 );
				eDelayCP.EnableWindow();
				ePercentCP.EnableWindow();
			}
			else
				MessageBox("Not a GCP potion!");
		break;
		case "itemHP":
			if (a_ItemInfo.ID.ClassID == 1539 || InStr( a_ItemInfo.Name, "Greater Healing Potion" ) > -1)
			{
				hHP.AddItem(a_ItemInfo);
				idHP = a_ItemInfo.ID;
				eDelayHP.SetString( "1" );
				eDelayHP.SetMaxLength( 2 );
				ePercentHP.SetString( "99" );
				ePercentHP.SetMaxLength( 2 );
				eDelayHP.EnableWindow();
				ePercentHP.EnableWindow();
			}
				
			else
				MessageBox("Not a GHP potion!");
		break;
		case "itemMP":
			if (a_ItemInfo.ID.ClassID == 728 || InStr( a_ItemInfo.Name, "Mana Potion" ) > -1)
			{
				hMP.AddItem(a_ItemInfo);
				idMP = a_ItemInfo.ID;
				eDelayMP.SetString( "1" );
				eDelayMP.SetMaxLength( 2 );
				ePercentMP.SetString( "99" );
				ePercentMP.SetMaxLength( 2 );
				eDelayMP.EnableWindow();
				ePercentMP.EnableWindow();
			}	
			else
				MessageBox("Not a Mana potion!");
		break;
		case "itemQHP":
			if (a_ItemInfo.ID.ClassID == 1540 || InStr( a_ItemInfo.Name, "Quick Healing Potion" ) > -1)
			{
				hQHP.AddItem(a_ItemInfo);
				idQHP = a_ItemInfo.ID;
				eDelayQHP.SetString( "1" );
				eDelayQHP.SetMaxLength( 2 );
				ePercentQHP.SetString( "99" );
				ePercentQHP.SetMaxLength( 2 );
				eDelayQHP.EnableWindow();
				ePercentQHP.EnableWindow();
			}			
			else
				MessageBox("Not a QHP potion!");
		break;
		case "itemSCP":
			if (a_ItemInfo.ID.ClassID == 5591 || InStr( a_ItemInfo.Name, "CP Potion" ) > -1)
			{
				hSCP.AddItem(a_ItemInfo);
				idSCP = a_ItemInfo.ID;
				eDelaySCP.SetString( "1" );
				eDelaySCP.SetMaxLength( 2 );
				ePercentSCP.SetString( "99" );
				ePercentSCP.SetMaxLength( 2 );
				eDelaySCP.EnableWindow();
				ePercentSCP.EnableWindow();
			}			
			else
				MessageBox("Not a CP potion!");
		break;
		case "itemSouls":
			if (a_ItemInfo.ID.ClassID == 10410 || InStr( a_ItemInfo.Name, "Full Bottle of Souls" ) > -1)
			{
				hS.AddItem(a_ItemInfo);
				idS = a_ItemInfo.ID;
				eAmountS.SetString( "40" );
				eAmountS.SetMaxLength( 2 );
				eAmountS.EnableWindow();
			}			
			else
				MessageBox("Not a Soul bottle!");
		break; //ItemType 5, ItemSubType 3 - For all potions
		case "itemRand1":
			if (isBuffPotion(a_ItemInfo))
			{
				hRand1.AddItem(a_ItemInfo);
				idRand1 = a_ItemInfo.ID;
			}			
			else
				MessageBox("Not a proper potion!");
		break;
		case "itemRand2":
			if (isBuffPotion(a_ItemInfo))
			{
				hRand2.AddItem(a_ItemInfo);
				idRand2 = a_ItemInfo.ID;
			}			
			else
				MessageBox("Not a proper potion!");
		break;
		case "itemRand3":
			if (isBuffPotion(a_ItemInfo))
			{
				hRand3.AddItem(a_ItemInfo);
				idRand3 = a_ItemInfo.ID;
			}			
			else
				MessageBox("Not a proper potion!");
		break;
	}
}

function OnClickItem( String strID, int index )
{
	local ItemInfo soulInfo;
	
	switch (strID)
	{
		case "itemCP":
			if (!useCP)
			{
				useCP = true;
				tBlankCP.HideWindow();
				StartAnim(aTex1);
				SetAutoTimer(TIMER_CP, eDelayCP, "itemCP");
				UsePotions(useCP, "CP", ePercentCP, hCP);
			}
		break;
		case "itemHP":
			if (!useHP)
			{
				useHP = true;
				tBlankHP.HideWindow();
				StartAnim(aTex2);
				SetAutoTimer(TIMER_HP, eDelayHP, "itemHP");
				UsePotions(useHP, "HP", ePercentHP, hHP);
			}
		break;
		case "itemMP":
			if (!useMP)
			{
				useMP = true;
				tBlankMP.HideWindow();
				StartAnim(aTex3);
				SetAutoTimer(TIMER_MP, eDelayMP, "itemMP");
				UsePotions(useMP, "MP", ePercentMP, hMP);		
			}	
		break;
		case "itemQHP":
			if (!useQHP)
			{
				useQHP = true;
				tBlankQHP.HideWindow();
				StartAnim(aTex4);
				SetAutoTimer(TIMER_QHP, eDelayQHP, "itemQHP");
				UsePotions(useQHP, "HP", ePercentQHP, hQHP);
			}			
		break;
		case "itemSCP":
			if (!useSCP)
			{
				useSCP = true;
				tBlankSCP.HideWindow();
				StartAnim(aTex5);
				SetAutoTimer(TIMER_SCP, eDelaySCP, "itemSCP");
				UsePotions(useSCP, "CP", ePercentSCP, hSCP);
			}			
		break;
		case "itemSouls":
			if (!useS)
			{
				useS = true;
				tBlankS.HideWindow();
				StartAnim(aTex6);
				InvItem.GetItem( InvItem.FindItem(GetItemIDByHandle(hS)), soulInfo );
				if (soulInfo.ItemNum > IntToInt64(0))
					RequestUseItem(soulInfo.ID);
			}			
		break;
		case "itemRand1":
			if (!useRand1)
			{
				useRand1 = true;
				tBlankRand1.HideWindow();
				StartAnim(aTex7);
			}			
		break;
		case "itemRand2":
			if (!useRand2)
			{
				useRand2 = true;
				tBlankRand2.HideWindow();
				StartAnim(aTex8);
			}			
		break;
		case "itemRand3":
			if (!useRand3)
			{
				useRand3 = true;
				tBlankRand3.HideWindow();
				StartAnim(aTex9);
			}			
		break;
	}
}

function OnRClickItem( String strID, int index )
{
	switch (strID)
	{
		case "itemCP":
			useCP = false;
			StopAnim(aTex1);
			Me.KillTimer(TIMER_CP);
		break;
		case "itemHP":
			useHP = false;
			StopAnim(aTex2);
			Me.KillTimer(TIMER_HP);
		break;
		case "itemMP":
			useMP = false;
			StopAnim(aTex3);
			Me.KillTimer(TIMER_MP);
		break;
		case "itemQHP":
			useQHP = false;
			StopAnim(aTex4);
			Me.KillTimer(TIMER_QHP);
		break;
		case "itemSCP":
			useSCP = false;
			StopAnim(aTex5);
			Me.KillTimer(TIMER_SCP);
		break;
		case "itemSouls":
			useS = false;
			StopAnim(aTex6);
		break;
		case "itemRand1":
			useRand1 = false;
			StopAnim(aTex7);
		break;
		case "itemRand2":
			useRand2 = false;
			StopAnim(aTex8);
		break;
		case "itemRand3":
			useRand3 = false;
			StopAnim(aTex9);
		break;
	}
}

function SetAutoTimer(int id, EditBoxHandle handle, string str)
{
	Me.KillTimer(id);
	if (int(handle.GetString()) > 0 && int(handle.GetString()) <= 30)
		Me.SetTimer(id, int(handle.GetString()) * 1000);
	else
	{
		MessageBox("Value between 1-30");
		OnRClickItem( str, 0 );
	}
		
}

function OnTimer(int TimerID)
{
	if (TimerID == TIMER_CP)
	{
		Me.KillTimer(TIMER_CP);
		UsePotions(useCP, "CP", ePercentCP, hCP);
		Me.SetTimer(TIMER_CP, int(eDelayCP.GetString()) * 1000);
	}
	else if (TimerID == TIMER_HP)
	{
		Me.KillTimer(TIMER_HP);
		UsePotions(useHP, "HP", ePercentHP, hHP);
		Me.SetTimer(TIMER_HP, int(eDelayHP.GetString()) * 1000);
	}
	else if (TimerID == TIMER_MP)
	{
		Me.KillTimer(TIMER_MP);
		UsePotions(useMP, "MP", ePercentMP, hMP);
		Me.SetTimer(TIMER_MP, int(eDelayMP.GetString()) * 1000);
	}
	else if (TimerID == TIMER_QHP)
	{
		Me.KillTimer(TIMER_QHP);
		UsePotions(useQHP, "HP", ePercentQHP, hQHP);
		Me.SetTimer(TIMER_QHP, int(eDelayQHP.GetString()) * 1000);
	}
	else if (TimerID == TIMER_SCP)
	{
		Me.KillTimer(TIMER_SCP);
		UsePotions(useSCP, "CP", ePercentSCP, hSCP);
		Me.SetTimer(TIMER_SCP, int(eDelaySCP.GetString()) * 1000);
	}
}

function StartAnim(AnimTextureHandle handle)
{
	handle.ShowWindow();
	handle.Stop();
	handle.SetLoopCount(-1);
	handle.Play();
}

function StopAnim(AnimTextureHandle handle)
{
	handle.HideWindow();
	handle.Stop();
}

function OnEvent(int EventID, string param)
{
	switch(EventID)
	{
		case EV_GamingStateEnter:
			SetToDefault();
		break;
		case EV_AbnormalStatusEtcItem:
			if (useS)
				UseBottles(eAmountS, hS, param);
		break;
		case EV_AbnormalStatusNormalItem:
			if (useRand1)
			{
				UseBuffPotions(hRand1, param);
				//sysDebug("USE 1");
			}
			if (useRand2)
			{
				UseBuffPotions(hRand2, param);
				//sysDebug("USE 2");
			}
			if (useRand3)
			{
				UseBuffPotions(hRand3, param);
				//sysDebug("USE 3");
			}
		break;
	}
}

function SetToDefault()
{
	// ---- Custom: Forçar o fundo cinza a encolher e esconder os extras ----
	Me.SetWindowSize(215, 90);
	
	// Esconde os botões e texturas de expansão que empurram a janela para baixo
	bExpand.HideWindow();
	bExpandMore.HideWindow();
	tDivider.HideWindow();
	tDivider2.HideWindow();
	
	// ---- Custom: APAGAR OS QUADRADINHOS VAZIOS QUE SOBRARAM ----
	hQHP.HideWindow();
	hSCP.HideWindow();
	hS.HideWindow();
	hRand1.HideWindow();
	hRand2.HideWindow();
	hRand3.HideWindow();
	
	tBlankQHP.HideWindow();
	tBlankSCP.HideWindow();
	tBlankS.HideWindow();
	tBlankRand1.HideWindow();
	tBlankRand2.HideWindow();
	tBlankRand3.HideWindow();
	
	ePercentQHP.HideWindow();
	ePercentSCP.HideWindow();
	eDelayQHP.HideWindow();
	eDelaySCP.HideWindow();
	eAmountS.HideWindow();
	// ----------------------------------------------------------------------

	useCP = false;
	useHP = false;
	useMP = false;
	useQHP = false;
	useSCP = false;
	useS = false;
	useRand1 = false;
	useRand2 = false;
	useRand3 = false;

	
	aTex1.HideWindow();
	aTex2.HideWindow();
	aTex3.HideWindow();
	aTex4.HideWindow();
	aTex5.HideWindow();
	aTex6.HideWindow();
	aTex7.HideWindow();
	aTex8.HideWindow();
	aTex9.HideWindow();
	
	hCP.Clear();
	hHP.Clear();
	hMP.Clear();
	hQHP.Clear();
	hSCP.Clear();
	hS.Clear();
	hRand1.Clear();
	hRand2.Clear();
	hRand3.Clear();
	
	tBlankCP.ShowWindow();
	tBlankHP.ShowWindow();
	tBlankMP.ShowWindow();
	tBlankQHP.ShowWindow();
	tBlankSCP.ShowWindow();
	tBlankS.ShowWindow();
	tBlankRand1.ShowWindow();
	tBlankRand2.ShowWindow();
	tBlankRand3.ShowWindow();
	
	ClearItemID( idCP );
	ClearItemID( idHP );
	ClearItemID( idMP );
	ClearItemID( idQHP );
	ClearItemID( idSCP );
	ClearItemID( idS );
	ClearItemID( idRand1 );
	ClearItemID( idRand2 );
	ClearItemID( idRand3 );
	
	eDelayCP.EnableWindow();
	eDelayHP.EnableWindow();
	eDelayMP.EnableWindow();
	eDelayQHP.EnableWindow();
	eDelaySCP.EnableWindow();
	eAmountS.EnableWindow();
	
	ePercentCP.EnableWindow();
	ePercentHP.EnableWindow();
	ePercentMP.EnableWindow();
	ePercentQHP.EnableWindow();
	ePercentSCP.EnableWindow();
	
	ePercentCP.SetString("");
	ePercentHP.SetString("");
	ePercentMP.SetString("");
	ePercentQHP.SetString("");
	ePercentSCP.SetString("");
	eDelayCP.SetString("");
	eDelayHP.SetString("");
	eDelayMP.SetString("");
	eDelayQHP.SetString("");
	eDelaySCP.SetString("");
	eAmountS.SetString("");
	
	// Define um limite de 2 dígitos (1-99%) para as caixas aceitarem digitação
	ePercentCP.SetMaxLength(2);
	ePercentHP.SetMaxLength(2);
	ePercentMP.SetMaxLength(2);
	ePercentQHP.SetMaxLength(2);
	ePercentSCP.SetMaxLength(2);
	
	// Define um limite de 4 dígitos para os delays de milissegundos
	eDelayCP.SetMaxLength(4);
	eDelayHP.SetMaxLength(4);
	eDelayMP.SetMaxLength(4);
	eDelayQHP.SetMaxLength(4);
	eDelaySCP.SetMaxLength(4);
	eAmountS.SetMaxLength(4);
	
	ePercentCP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
	ePercentHP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
	ePercentMP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
	ePercentQHP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
	ePercentSCP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
	
	eAmountS.SetTooltipCustomType(MakeTooltipSimpleText("Value should be 10, 15,.., 40"));
}

function UsePotions(bool bUse, string whatUse, EditBoxHandle eHandle, ItemWindowHandle hHandle)
{
	local ItemInfo info;
	local UserInfo pInfo;
	
	local int percent;
	
	if (IsWrongCondition())
	{
		return;
	}
	
	GetPlayerInfo(pInfo);
	
	if (whatUse == "CP")
		percent = int(float(pInfo.nCurCP)/float(pInfo.nMaxCP) * float(100));
	else if (whatUse == "HP")
		percent = int(float(pInfo.nCurHP)/float(pInfo.nMaxHP) * float(100));
	else
		percent = int(float(pInfo.nCurMP)/float(pInfo.nMaxMP) * float(100));
	
	
	if (bUse && percent <= int(eHandle.GetString()))
	{
		InvItem.GetItem( InvItem.FindItem(GetItemIDByHandle(hHandle)), info );
		if (info.ItemNum > IntToInt64(0))
			RequestUseItem(info.ID);
		else
			ClearOnNoItems(hHandle);
	}
}

function bool IsWrongCondition()
{
	local int i;
	local int j;
	local int k;
	local int RowCount;
	local int ColCount;
	local StatusIconInfo info;
	local StatusIconHandle StatusIcon;
	local array<string> invSkills;
	
	StatusIcon = GetStatusIconHandle( "AbnormalStatusWnd.StatusIcon" );
	
	invSkills[0] = "Turn to Stone";
	invSkills[1] = "Hide";
	invSkills[2] = "Sonic Barrier";
	invSkills[3] = "Force Barrier";
	invSkills[4] = "Enchanter Ability - Barrier";
	invSkills[5] = "Celestial Shield";
	invSkills[6] = "Painkiller";
	
	RowCount = StatusIcon.GetRowCount();
	for (i = 0; i < RowCount; i++)
	{
		ColCount = StatusIcon.GetColCount(i);
		for (j = 0; j < ColCount; j++)
		{
			StatusIcon.GetItem(i, j, info);
			
			for (k = 0; k < invSkills.Length; k++)
			{
				if (info.Name == invSkills[k])
				{
					return true;
				}
			}	
		}
	}
	
	return false;
}

function UseBottles(EditBoxHandle eHandle, ItemWindowHandle hHandle, string param)
{
	local ItemInfo info;
	local StatusIconInfo infoIcon;
	
	local int soulCount;
	local int idx;
	local int tempMaxRange;
	local int Max;
	
	ParseInt(param, "Max", Max);
	for (idx = 0; idx < Max; idx++)
	{
		ParseItemIDWithIndex(param, infoIcon.ID, idx);
		ParseInt(param, "SkillLevel_" $ idx, infoIcon.Level);
		ParseString(param, "Name_" $ idx, infoIcon.Name);
		
		if (infoIcon.Name == "Soul Expansion")
		{
			soulCount = infoIcon.Level;
			break;
		}
	}
	
	//sysDebug("eCount = "$eHandle.GetString());
	
	if ( soulCount < 10 && soulCount != 0)
	{
		tempMaxRange = (int(eHandle.GetString()) / 5 - 1);
		for (idx = 0; idx < tempMaxRange; idx++)
		{
				InvItem.GetItem( InvItem.FindItem(GetItemIDByHandle(hHandle)), info );
				if (info.ItemNum > IntToInt64(0))
				{
					RequestUseItem(info.ID);
				}
				else
				{
					ClearOnNoItems(hHandle);
					break;
				}		
		}
	}
}

function UseBuffPotions(ItemWindowHandle hHandle, string param)
{
	local ItemInfo info;
	local StatusIconInfo infoIcon;
	
	local int idx;
	local int Max;
	local int countBuff;
	
	countBuff = 0;
	
	info.Name = "";
	
	ParseInt(param, "Max", Max);
	for (idx = 0; idx < Max; idx++)
	{
		ParseItemIDWithIndex(param, infoIcon.ID, idx);
		ParseInt(param, "SkillLevel_" $ idx, infoIcon.Level);
		ParseString(param, "Name_" $ idx, infoIcon.Name);
		
		//Check if acumen exist
		if (InStr(infoIcon.Name, "Greater Magic Haste Potion") > -1)
		{
			countBuff++;
			//sysDebug("ACUMEN ++");
		}
		
		if (countBuff > 0)
		{
			isAcumExist = true;
			break;
		}
		else
		{
			isAcumExist = false;
			continue;
		}
	}
	
	if (!isAcumExist)
	{
		InvItem.GetItem( InvItem.FindItem(GetItemIDByHandle(hHandle)), info );
		if (info.Name == "Greater Magic Haste Potion")
		{
			if (info.ItemNum > IntToInt64(0))
			{
				RequestUseItem(info.ID);
				isAcumExist = true;
				return;
			}
			else
			{
				ClearOnNoItems(hHandle);
				return;
			}
		}
	}
	
	countBuff = 0;
	info.Name = "";
	
	for (idx = 0; idx < Max; idx++)
	{
		ParseItemIDWithIndex(param, infoIcon.ID, idx);
		ParseInt(param, "SkillLevel_" $ idx, infoIcon.Level);
		ParseString(param, "Name_" $ idx, infoIcon.Name);
		
		//Check if ww exist
		if (InStr(infoIcon.Name, "Greater Haste Potion") > -1)
		{
			countBuff++;
			//sysDebug("WW ++");
		}
		
		if (countBuff > 0)
		{
			isWWExist = true;
			break;
		}
		else
		{
			isWWExist = false;
			continue;
		}
		
	}
	
	if (!isWWExist)
	{
		InvItem.GetItem( InvItem.FindItem(GetItemIDByHandle(hHandle)), info );
		if (info.Name == "Greater Haste Potion")
		{
			if (info.ItemNum > IntToInt64(0))
			{
				RequestUseItem(info.ID);
				isWWExist = true;
				return;
			}
			else
			{
				ClearOnNoItems(hHandle);
				return;
			}
		}
	}
	
	countBuff = 0;
	info.Name = "";
	
	for (idx = 0; idx < Max; idx++)
	{
		ParseItemIDWithIndex(param, infoIcon.ID, idx);
		ParseInt(param, "SkillLevel_" $ idx, infoIcon.Level);
		ParseString(param, "Name_" $ idx, infoIcon.Name);
		
		
		//Check if haste exist
		if (InStr(infoIcon.Name, "Greater Swift Attack Potion") > -1)
		{
			countBuff++;
			//sysDebug("HASTE ++");
		}
		
		if (countBuff > 0)
		{
			isHasteExist = true;
			break;
		}
		else
		{
			isHasteExist = false;
			continue;
		}
	}
	
	if (!isHasteExist)
	{
		InvItem.GetItem( InvItem.FindItem(GetItemIDByHandle(hHandle)), info );
		if (info.Name == "Greater Swift Attack Potion")
		{
			if (info.ItemNum > IntToInt64(0))
			{
				RequestUseItem(info.ID);
				isHasteExist = true;
				return;
			}
			else
			{
				ClearOnNoItems(hHandle);
				return;
			}
		}
	}
}

function ItemID GetItemIDByHandle(ItemWindowHandle hHandle)
{
	local ItemID item;
	
	switch (hHandle)
	{
		case hCP:
			item = idCP;
		break;
		case hHP:
			item = idHP;
		break;
		case hMP:
			item = idMP;
		break;
		case hQHP:
			item = idQHP;
		break;
		case hSCP:
			item = idSCP;
		break;
		case hS:
			item = idS;
		break;
		case hRand1:
			item = idRand1;
		break;
		case hRand2:
			item = idRand2;
		break;
		case hRand3:
			item = idRand3;
		break;
	}

	return item;
}

function ClearOnNoItems(ItemWindowHandle hHandle)
{
	
	
	hHandle.Clear();
	switch (hHandle)
	{
		case hCP:
			tBlankCP.ShowWindow();
			OnRClickItem( "itemCP", 0 );
		break;
		case hHP:
			tBlankHP.ShowWindow();
			OnRClickItem( "itemHP", 0 );
		break;
		case hMP:
			tBlankMP.ShowWindow();
			OnRClickItem( "itemMP", 0 );
		break;
		case hQHP:
			tBlankQHP.ShowWindow();
			OnRClickItem( "itemQHP", 0 );
		break;
		case hSCP:
			tBlankSCP.ShowWindow();
			OnRClickItem( "itemSCP", 0 );
		break;
		case hS:
			tBlankS.ShowWindow();
			OnRClickItem( "itemSouls", 0 );
		break;
		case hRand1:
			tBlankRand1.ShowWindow();
			OnRClickItem( "itemRand1", 0 );
		break;
		case hRand2:
			tBlankRand2.ShowWindow();
			OnRClickItem( "itemRand2", 0 );
		break;
		case hRand3:
			tBlankRand3.ShowWindow();
			OnRClickItem( "itemRand3", 0 );
		break;
	}
}

function OnClickButton(string strID)
{
	switch(strID)
	{
		case "expandBtn":
			OnClickExpand();
		break;
		case "expandMoreBtn":
			OnClickExpandMore();
		break;
	}
}

function OnClickExpand()
{
	local Rect wSize;
	
	wSize = Me.GetRect();
	if (wSize.nHeight == 186)
	{
		Me.SetWindowSize(wSize.nWidth, 90);
		tDivider.HideWindow();
		hQHP.HideWindow();
		hSCP.HideWindow();
		hS.HideWindow();
		ePercentQHP.HideWindow();
		ePercentSCP.HideWindow();
		eDelayQHP.HideWindow();
		eDelaySCP.HideWindow();
		eAmountS.HideWindow();
		txtPercentQHP.HideWindow();
		txtPercentSCP.HideWindow();
		txtDelayQHP.HideWindow();
		txtDelaySCP.HideWindow();
		txtDescQHP.HideWindow();
		txtDescSCP.HideWindow();
		txtDescS.HideWindow();
		bExpandMore.HideWindow();
		bExpand.SetTexture( "L2UI_CH3.ShortcutWnd.shortcut_minimize", "L2UI_CH3.ShortcutWnd.shortcut_minimize_down", "L2UI_CH3.ShortcutWnd.shortcut_minimize_over" );
	}
	else
	{
		Me.SetWindowSize(wSize.nWidth, 186);
		tDivider.ShowWindow();
		hQHP.ShowWindow();
		hSCP.ShowWindow();
		hS.ShowWindow();
		ePercentQHP.ShowWindow();
		ePercentSCP.ShowWindow();
		eDelayQHP.ShowWindow();
		eDelaySCP.ShowWindow();
		eAmountS.ShowWindow();
		txtPercentQHP.ShowWindow();
		txtPercentSCP.ShowWindow();
		txtDelayQHP.ShowWindow();
		txtDelaySCP.ShowWindow();
		txtDescQHP.ShowWindow();
		txtDescSCP.ShowWindow();
		txtDescS.ShowWindow();
		bExpandMore.ShowWindow();
		bExpand.SetTexture( "L2UI_CH3.ShortcutWnd.shortcut_expand", "L2UI_CH3.ShortcutWnd.shortcut_expand_down", "L2UI_CH3.ShortcutWnd.shortcut_expand_over" );
	}
}

function OnClickExpandMore()
{
	local Rect wSize;
	
	wSize = Me.GetRect();
	if (wSize.nHeight == 262)
	{
		Me.SetWindowSize(wSize.nWidth, 186);
		tDivider2.HideWindow();
		hRand1.HideWindow();
		hRand2.HideWindow();
		hRand3.HideWindow();
		bExpand.ShowWindow();
		txtDescRand1.HideWindow();
		txtDescRand2.HideWindow();
		txtDescRand3.HideWindow();
		bExpandMore.SetTexture( "L2UI_CH3.ShortcutWnd.shortcut_minimize", "L2UI_CH3.ShortcutWnd.shortcut_minimize_down", "L2UI_CH3.ShortcutWnd.shortcut_minimize_over" );
	}
	else if (wSize.nHeight == 186)
	{
		Me.SetWindowSize(wSize.nWidth, 262);
		tDivider2.ShowWindow();
		hRand1.ShowWindow();
		hRand2.ShowWindow();
		hRand3.ShowWindow();
		bExpand.HideWindow();
		txtDescRand1.ShowWindow();
		txtDescRand2.ShowWindow();
		txtDescRand3.ShowWindow();
		bExpandMore.SetTexture( "L2UI_CH3.ShortcutWnd.shortcut_expand", "L2UI_CH3.ShortcutWnd.shortcut_expand_down", "L2UI_CH3.ShortcutWnd.shortcut_expand_over" );
	}
}