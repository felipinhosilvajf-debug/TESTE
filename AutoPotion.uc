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

// Slots que agora operam as Skills do AutoFarm
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

// IDs locais para guardar as skills do farm
var int currentSkillId1;
var int currentSkillId2;
var int currentSkillId3;

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
	
	SetToDefault(); 
	Me.SetWindowSize(215, 262);
}

function OnShow()
{
	Me.SetWindowSize(215, 262);
	
	tDivider.ShowWindow();
	tDivider2.ShowWindow();
	hQHP.ShowWindow();
	hSCP.ShowWindow();
	hS.ShowWindow();
	hRand1.ShowWindow();
	hRand2.ShowWindow();
	hRand3.ShowWindow();
	
	txtDescQHP.HideWindow();
	txtDescSCP.HideWindow();
	txtDescS.HideWindow();
	txtDescRand1.ShowWindow();
	txtDescRand2.ShowWindow();
	txtDescRand3.ShowWindow();
	
	txtPercentQHP.HideWindow();
	txtPercentSCP.HideWindow();
	txtDelayQHP.HideWindow();
	txtDelaySCP.HideWindow();
	
	Me.ShowWindow();
	Me.SetFocus();
}

function OnDropItem( String a_WindowID, ItemInfo a_ItemInfo, int X, int Y)
{
	local int droppedSkillID;
	droppedSkillID = a_ItemInfo.ID.ClassID;

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
		break;
		// ============================================================
		// CORREÇÃO CONTRA BYPASS: GRAVA SKILL VIA COMANDO DE CHAT SEGURO
		// ============================================================
		case "itemRand1":
			currentSkillId1 = droppedSkillID;
			hRand1.AddItem(a_ItemInfo);
			tBlankRand1.HideWindow();
			ExecuteCommand(".autofarm select 1 " $ currentSkillId1);
		break;
		case "itemRand2":
			currentSkillId2 = droppedSkillID;
			hRand2.AddItem(a_ItemInfo);
			tBlankRand2.HideWindow();
			ExecuteCommand(".autofarm select 2 " $ currentSkillId2);
		break;
		case "itemRand3":
			currentSkillId3 = droppedSkillID;
			hRand3.AddItem(a_ItemInfo);
			tBlankRand3.HideWindow();
			ExecuteCommand(".autofarm select 3 " $ currentSkillId3);
		break;
	}
}

function OnClickItem( String strID, int index )
{
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

				// ============================================================
		// CORREÇÃO CONTRA TRAVA DE BYPASS: EXECUTA VIA GATILHO DE CHAT
		// ============================================================
		case "itemRand1":
		case "itemRand2":
		case "itemRand3":
			// Simula o jogador digitando o comando puro no chat (Passe livre do Core)
			ExecuteCommand(".autofarm toggle");
			PlayConsoleSound(IFST_CLICK1);
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
		// ============================================================
		// CORREÇÃO CONTRA BYPASS: REMOVE SKILL VIA COMANDO DE CHAT SEGURO
		// ============================================================
		case "itemRand1":
			currentSkillId1 = 0;
			hRand1.Clear();
			tBlankRand1.ShowWindow();
			ExecuteCommand(".autofarm select 1 0");
		break;
		case "itemRand2":
			currentSkillId2 = 0;
			hRand2.Clear();
			tBlankRand2.ShowWindow();
			ExecuteCommand(".autofarm select 2 0");
		break;
		case "itemRand3":
			currentSkillId3 = 0;
			hRand3.Clear();
			tBlankRand3.ShowWindow();
			ExecuteCommand(".autofarm select 3 0");
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
local int slot;
local int skillId;
local ItemInfo fakeSkillInfo;
local string tmpSkill;
if (EventID == EV_GamingStateEnter)
{
SetToDefault();
return;
}
if (InStr(param, "autoFarm_slot") > -1)
{
if (InStr(param, "slot1_") > -1)
{
slot = 1;
tmpSkill = Mid(param, 14);
skillId = int(tmpSkill);
}
else if (InStr(param, "slot2_") > -1)
{
slot = 2;
tmpSkill = Mid(param, 14);
skillId = int(tmpSkill);
}
else if (InStr(param, "slot3_") > -1)
{
slot = 3;
tmpSkill = Mid(param, 14);
skillId = int(tmpSkill);
}
if (skillId > 0 && slot > 0)
{
fakeSkillInfo.ID.ClassID = skillId;
switch (slot)
{
case 1:
currentSkillId1 = skillId;
hRand1.AddItem(fakeSkillInfo);
tBlankRand1.HideWindow();
break;
case 2:
currentSkillId2 = skillId;
hRand2.AddItem(fakeSkillInfo);
tBlankRand2.HideWindow();
break;
case 3:
currentSkillId3 = skillId;
hRand3.AddItem(fakeSkillInfo);
tBlankRand3.HideWindow();
break;
}
}
else if (slot > 0)
{
switch (slot)
{
case 1: currentSkillId1 = 0; hRand1.Clear(); tBlankRand1.ShowWindow(); break;
case 2: currentSkillId2 = 0; hRand2.Clear(); tBlankRand2.ShowWindow(); break;
case 3: currentSkillId3 = 0; hRand3.Clear(); tBlankRand3.ShowWindow(); break;
}
}
}
}
function SetToDefault()
{
Me.SetWindowSize(215, 262);
bExpand.HideWindow();
bExpandMore.HideWindow();
tDivider.ShowWindow();
tDivider2.ShowWindow();
hQHP.ShowWindow();
hSCP.ShowWindow();
hS.ShowWindow();
hRand1.ShowWindow();
hRand2.ShowWindow();
hRand3.ShowWindow();
tBlankQHP.ShowWindow();
tBlankSCP.ShowWindow();
tBlankS.ShowWindow();
tBlankRand1.ShowWindow();
tBlankRand2.ShowWindow();
tBlankRand3.ShowWindow();
useCP = false;
useHP = false;
useMP = false;
useQHP = false;
useSCP = false;
currentSkillId1 = 0;
currentSkillId2 = 0;
currentSkillId3 = 0;
aTex1.HideWindow();
aTex2.HideWindow();
aTex3.HideWindow();
aTex4.HideWindow();
aTex5.HideWindow();
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
ClearItemID( idCP );
ClearItemID( idHP );
ClearItemID( idMP );
ClearItemID( idQHP );
ClearItemID( idSCP );
eDelayCP.EnableWindow();
eDelayHP.EnableWindow();
eDelayMP.EnableWindow();
eDelayQHP.EnableWindow();
eDelaySCP.EnableWindow();
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
ePercentCP.SetMaxLength(2);
ePercentHP.SetMaxLength(2);
ePercentMP.SetMaxLength(2);
ePercentQHP.SetMaxLength(2);
ePercentSCP.SetMaxLength(2);
eDelayCP.SetMaxLength(4);
eDelayHP.SetMaxLength(4);
eDelayMP.SetMaxLength(4);
eDelayQHP.SetMaxLength(4);
eDelaySCP.SetMaxLength(4);
ePercentCP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
ePercentHP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
ePercentMP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
ePercentQHP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
ePercentSCP.SetTooltipCustomType(MakeTooltipSimpleText("Value should be between 1 - 99"));
}
function UsePotions(bool bUse, string whatUse, EditBoxHandle eHandle, ItemWindowHandle hHandle)
{
local ItemInfo info;
local UserInfo pInfo;
local int percent;
if (IsWrongCondition())
return;
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

function ClearOnNoItems(ItemWindowHandle hHandle)
{
hHandle.Clear();
switch (hHandle)
{
case hCP: tBlankCP.ShowWindow(); break;
case hHP: tBlankHP.ShowWindow(); break;
case hMP: tBlankMP.ShowWindow(); break;
}
}
function ItemID GetItemIDByHandle(ItemWindowHandle hHandle)
{
local ItemID item;
switch (hHandle)
{
case hCP: item = idCP; break;
case hHP: item = idHP; break;
case hMP: item = idMP; break;
}
return item;
}
// ====================================================================
// FUNÇÃO ÚNICA E SOBERANA QUE CONTROLA OS CLIQUES DE BOTÕES NATIVOS
// ====================================================================
function OnClickButton(string strID)
{
switch(strID)
{
case "expandBtn":
// Caso queira encolher/esticar o painel superior futuramente
break;
case "expandMoreBtn":
// INTERRUPTOR OFICIAL: Lança o comando silencioso direto pro seu Java!
RequestBypassToServer("bypass voice_autofarm toggle");
PlayConsoleSound(IFST_CLICK1);
break;
}
}