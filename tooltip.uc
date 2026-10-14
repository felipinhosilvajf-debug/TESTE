class Tooltip extends UICommonAPI;

const TOOLTIP_MINIMUM_WIDTH = 144;
const TOOLTIP_SETITEM_MAX = 3;

const ATTRIBUTE_FIRE 	= 0;
const ATTRIBUTE_WATER 	= 1;
const ATTRIBUTE_WIND 	= 2;
const ATTRIBUTE_EARTH 	= 3;
const ATTRIBUTE_HOLY 	= 4;
const ATTRIBUTE_UNHOLY 	= 5;

var CustomTooltip m_Tooltip;
var DrawItemInfo m_Info;

var Array<int> AttackAttLevel;
var Array<int> AttackAttCurrValue;
var Array<int> AttackAttMaxValue; //모든 공격 속성의 레벨, 현재레벨에서의 값, 현재레벨에서의 최대값을 여기에 저장한다.

var Array<int> DefAttLevel;
var Array<int> DefAttCurrValue;
var Array<int> DefAttMaxValue; //모든 방어 속성의 레벨, 현재레벨에서의 값, 현재레벨에서의 최대값을 여기에 저장한다.

var int NowAttrLv;
var int NowMaxValue;
var int NowValue;

var bool BoolSelect;
var Color Red, Yellow, Bronze, Gray;

function OnRegisterEvent()
{
	RegisterEvent( EV_RequestTooltipInfo );
}

function OnLoad()
{
	BoolSelect = true;	// 숏컷 툴팁 켜기/끄기 기본값을 켜기로(TTP#41925) 2010.8.23 - winkey
	
	Red.R = 255;
	Red.G = 0;
	Red.B = 0;
	Red.A = 255;
	Yellow.R = 255;
	Yellow.G = 215;
	Yellow.B = 0;
	Yellow.A = 255;
	Bronze.R = 176;
	Bronze.G = 155;
	Bronze.B = 121;	
	Bronze.A = 255;
	Gray.R = 163;
	Gray.G = 163;
	Gray.B = 163;
	Gray.A = 255;
	
	if(CREATE_ON_DEMAND==0)
		OnRegisterEvent();
}

function OnEvent(int Event_ID, string param)
{
	switch( Event_ID )
	{
	case EV_RequestTooltipInfo:
		//debug("툴팁이벤트 넘어오냐");
		HandleRequestTooltipInfo(param);
		break;
	}
}

function setBoolSelect( bool b )
{
	BoolSelect = b;
}

function HandleRequestTooltipInfo(string param)
{
	local String TooltipType;
	local int SourceType;
	local ETooltipSourceType eSourceType;
	
	ClearTooltip();
	
	if (!ParseString(param, "TooltipType", TooltipType))
		return;
		
	if (!ParseInt(param, "SourceType", SourceType))
		return;
	
	eSourceType = ETooltipSourceType(SourceType);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////// Normal Tooltip /////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	//debug("Tooltip타입:"$TooltipType);
	if (TooltipType == "Text")
	{
		ReturnTooltip_NTT_TEXT(param, eSourceType, false);
	}
	else if (TooltipType == "Description")
	{
		ReturnTooltip_NTT_TEXT(param, eSourceType, true);
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////// ItemWnd Tooltip ////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	else if (TooltipType == "Action")
	{
		ReturnTooltip_NTT_ACTION(param, eSourceType);
	}
	else if (TooltipType == "Skill")
	{
		ReturnTooltip_NTT_SKILL(param, eSourceType);
	}
	else if (TooltipType == "NormalItem")
	{
		ReturnTooltip_NTT_NORMALITEM(param, eSourceType);
	}
	else if (TooltipType == "Shortcut")
	{
		ReturnTooltip_NTT_SHORTCUT(param, eSourceType);
	}
	else if (TooltipType == "AbnormalStatus")
	{
		ReturnTooltip_NTT_ABNORMALSTATUS(param, eSourceType);
	}
	else if (TooltipType == "RecipeManufacture")
	{
		ReturnTooltip_NTT_RECIPE_MANUFACTURE(param, eSourceType);
	}
	else if (TooltipType == "RecipeManufactureBuy")
	{
		ReturnTooltip_NTT_RECIPE_MANUFACTUREBUY(param, eSourceType);
	}
	else if (TooltipType == "Recipe")
	{
		ReturnTooltip_NTT_RECIPE(param, eSourceType, false);
	}
	else if (TooltipType == "RecipePrice")
	{
		ReturnTooltip_NTT_RECIPE(param, eSourceType, true);
	}
	else if (TooltipType == "Inventory"
			|| TooltipType == "InventoryPrice1"
			|| TooltipType == "InventoryPrice2"
			|| TooltipType == "InventoryPrice1HideEnchant"
			|| TooltipType == "InventoryPrice1HideEnchantStackable"
			|| TooltipType == "InventoryPrice2PrivateShop"
			|| TooltipType == "InventoryWithIcon"
			|| TooltipType == "InventoryPawnViewer"		// PawnViewer용 추가 - lancelot 2007. 10. 16.
			|| TooltipType == "TaliList")
	{
		//~ debug("툴팁보여주세요.");
		ReturnTooltip_NTT_ITEM(param, TooltipType, eSourceType);
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////// ListCtrl Tooltip ///////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	//선준 수정(2010.02.22 ~ 03.08) 완료
	else if ( TooltipType == "RoomList" )
	{
		ReturnTooltip_NTT_ROOMLIST(param, eSourceType);
	}	
	else if ( TooltipType == "UserList" )
	{
		ReturnTooltip_NTT_USERLIST(param, eSourceType);
	}
	else if (TooltipType == "PartyMatch")
	{
		ReturnTooltip_NTT_PARTYMATCH(param, eSourceType);
	}
	else if (TooltipType == "UnionList")
	{
		ReturnTooltip_NTT_UNIONLIST(param, eSourceType);
	}
	else if (TooltipType == "QuestInfo")
	{
		ReturnTooltip_NTT_QUESTINFO(param, eSourceType);
	}
	else if (TooltipType == "QuestList")
	{
		ReturnTooltip_NTT_QUESTLIST(param, eSourceType);
	}
	else if (TooltipType == "RaidList")
	{
		ReturnTooltip_NTT_RAIDLIST(param, eSourceType);
	}
	else if (TooltipType == "ClanInfo")
	{
		ReturnTooltip_NTT_CLANINFO(param, eSourceType);
	}
	else if (TooltipType == "RadarInfo")//by Merc
	{
		ReturnTooltip_NTT_RADARINFO(param, eSourceType);
	}
	else if (TooltipType == "AutoSS")//by Monokini
	{
		ReturnTooltip_NTT_AUTOSSINFO(param, eSourceType);
	}
	else if (TooltipType == "RadarResize")//by Monokini
	{
		ReturnTooltip_NTT_RADARRESIZE(param, eSourceType);
	}
	//선준 수정( 10.03.30 ) 완료
	//우편함에 툴팁 추가.
	else if (TooltipType == "PostInfo")
	{
		ReturnTooltip_NTT_POSTINFO(param, eSourceType);
	}
	/////////////////////////////////////////////////////
	// MANOR
	else if (TooltipType == "ManorSeedInfo"
			|| TooltipType == "ManorCropInfo"
			|| TooltipType == "ManorSeedSetting"
			|| TooltipType == "ManorCropSetting"
			|| TooltipType == "ManorDefaultInfo"
			|| TooltipType == "ManorCropSell")
	{
		ReturnTooltip_NTT_MANOR(param, TooltipType, eSourceType);
	}
	// [퀘스트 아이템 툴팁 추가]
	else if (TooltipType == "QuestItem")
	{
		ReturnTooltip_NTT_QUESTREWARDS(param, eSourceType);
	}
}

function bool IsEnchantableItem(EItemParamType Type)
{
	return (Type == ITEMP_WEAPON || Type == ITEMP_ARMOR || Type == ITEMP_ACCESSARY || Type == ITEMP_SHIELD);
}

function ClearTooltip()
{
	m_Tooltip.SimpleLineCount = 0;
	m_Tooltip.MinimumWidth = 0;
	m_Tooltip.DrawList.Remove(0, m_Tooltip.DrawList.Length);
}

function StartItem()
{
	local DrawItemInfo infoClear;
	m_Info = infoClear;
}

function EndItem()
{
	m_Tooltip.DrawList.Length = m_Tooltip.DrawList.Length + 1;
	m_Tooltip.DrawList[m_Tooltip.DrawList.Length-1] = m_Info;
}

/////////////////////////////////////////////////////////////////////////////////
// TEXT
function ReturnTooltip_NTT_TEXT(string param, ETooltipSourceType eSourceType, bool bDesc)
{
	local string strText;
	local int ID;
	local int i;
	local int j;
	local array<int> QID;
	local UserInfo info;

	if (eSourceType == NTST_TEXT)
	{
		if (ParseString( param, "Text", strText))
		{
			if (Len(strText)>0)
			{
				if (bDesc)
				{
					m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
					
					StartItem();
					m_Info.eType = DIT_TEXT;
					m_Info.t_color.R = 178;
					m_Info.t_color.G = 190;
					m_Info.t_color.B = 207;
					m_Info.t_color.A = 255;
					m_Info.t_strText = strText;
					EndItem();
				}
				else
				{
					if (ParseInt( strText, "#ID", ID))	//RadarMapObj - normal NPC
					{
						GetUserInfo(ID, info);
						
						if (info.strNickName != "")
						{	
							StartItem();						
							m_Info.eType = DIT_TEXT;
							m_Info.t_bDrawOneLine = true;
							m_Info.nOffSetX = 1;
							m_Info.nOffSetY = 2;
							m_Info.t_color = info.NicknameColor;		
							m_Info.t_strText = info.strNickName;
							EndItem();	
							
							StartItem();
							m_Info.eType = DIT_TEXT;
							m_Info.bLineBreak = true;
							m_Info.t_bDrawOneLine = true;
							m_Info.nOffSetX = 1;
							m_Info.nOffSetY = 2;
							m_Info.t_strText = info.Name;
							EndItem();
						}
						else	// normal text tooltip
						{
							AddTooltipSimpleName(info.Name);
						}	
					}
					else if (ParseInt(strText, "#Length", ID))	//RadarMapObj - NPC with Quest Emitter
					{
						QID.Length = ID;
						
						for (i = 0; i < ID; i++)
							ParseInt(strText, "#Q" $ string(i), QID[i]);
							
						for (i = 0; i < ID; i++)
						{
							for (j = 0; j < ID; j++)
								if ((QID[i] == QID[j]) && (i != j) && (QID[i] > 0)) QID[i] = -1;
						}
						
						ParseInt(strText, "#Name", i);
						GetUserInfo(i, info);					
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.t_bDrawOneLine = true;
						m_Info.nOffSetX = 1;
						m_Info.nOffSetY = 1;
						m_Info.t_strText = info.Name;
						EndItem();	
										
						for (i = 0; i < ID; i++)
						{
							if (QID[i] > 0)
							{								
								StartItem();
								m_Info.eType = DIT_TEXT;
								m_Info.bLineBreak = true;
								m_Info.t_bDrawOneLine = true;
								m_Info.nOffSetX = 1;
								m_Info.nOffSetY = 1;						
								m_Info.t_color.R = 127;
								m_Info.t_color.G = 114;
								m_Info.t_color.B = 89;
								m_Info.t_color.A = 255;
								m_Info.t_strText = class'UIDATA_QUEST'.static.GetQuestName(QID[i]);
								EndItem();
							}
						}						
					}
					else if (ParseInt(strText, "#QID", ID))	//RadarMapObj - Current Quest Target
					{
						ParseInt(strText, "#Level", i);
											
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.t_bDrawOneLine = true;
						m_Info.nOffSetY = 1;
						m_Info.nOffSetX = 1;
						m_Info.t_strText = class'UIDATA_QUEST'.static.GetTargetName(ID, i);
						EndItem();	
																
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.bLineBreak = true;
						m_Info.t_bDrawOneLine = true;
						m_Info.nOffSetX = 1;
						m_Info.nOffSetY = 1;
						m_Info.t_color.R = 127;
						m_Info.t_color.G = 114;
						m_Info.t_color.B = 89;
						m_Info.t_color.A = 255;
						m_Info.t_strText = class'UIDATA_QUEST'.static.GetQuestName(ID);
						EndItem();					
					}
					else
					{
						AddTooltipSimpleName(strText);
					}
				}
			}
		}
		else if (ParseInt( param, "ID", ID))
		{
			if (ID>0)
			{
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_ID = ID;
				EndItem();
			}
		}
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}


function string CheckHPEnchant(int SlotBitType, int CrystalType, int Enchant)
{
	local string enchantHP;
	
	enchantHP="";
	if  ((SlotBitType==32768) || (SlotBitType==1024) || (SlotBitType==256) || (SlotBitType==2048) || (SlotBitType==512)|| (SlotBitType==64) || (SlotBitType==1) || (SlotBitType==268435456)	|| (SlotBitType==4096) )
	{
		if (SlotBitType!=32768)
		{
			if (CrystalType == 1)
			{
				if (Enchant == 4) enchantHP="9";
				else if (Enchant == 5) enchantHP="26";
					else if (Enchant == 6) enchantHP="52";
						else if (Enchant == 7) enchantHP="86";
							else if (Enchant == 8) enchantHP="130";
								else if (Enchant == 9) enchantHP="181";
									else if (Enchant >= 10) enchantHP="242";
			}
			else if (CrystalType == 2)
			{
				if (Enchant == 4) enchantHP="12";
				else if (Enchant == 5) enchantHP="36";
					else if (Enchant == 6) enchantHP="71";
						else if (Enchant == 7) enchantHP="118";
							else if (Enchant == 8) enchantHP="178";
								else if (Enchant == 9) enchantHP="249";
									else if (Enchant >= 10) enchantHP="332";
			}
			else if (CrystalType == 3)
			{
				if (Enchant == 4) enchantHP="14";
				else if (Enchant == 5) enchantHP="42";
					else if (Enchant == 6) enchantHP="84";
						else if (Enchant == 7) enchantHP="139";
							else if (Enchant == 8) enchantHP="209";
								else if (Enchant == 9) enchantHP="293";
									else if (Enchant >= 10) enchantHP="390";
			}
			else if (CrystalType == 4)
			{
				if (Enchant == 4) enchantHP="16";
				else if (Enchant == 5) enchantHP="47";
					else if (Enchant == 6) enchantHP="94";
						else if (Enchant == 7) enchantHP="157";
							else if (Enchant == 8) enchantHP="235";
								else if (Enchant == 9) enchantHP="329";
									else if (Enchant >= 10) enchantHP="439";
			}
			else if ((CrystalType == 5) || (CrystalType == 6) || (CrystalType == 7))
			{
				if (Enchant == 4) enchantHP="17";
				else if (Enchant == 5) enchantHP="52";
					else if (Enchant == 6) enchantHP="104";
						else if (Enchant == 7) enchantHP="173";
							else if (Enchant == 8) enchantHP="259";
								else if (Enchant == 9) enchantHP="363";
									else if (Enchant >= 10) enchantHP="484";
			}
		}
		else if (SlotBitType==32768)
		{
			if (CrystalType == 1)
			{
				if (Enchant == 4) enchantHP="13";
				else if (Enchant == 5) enchantHP="39";
					else if (Enchant == 6) enchantHP="78";
						else if (Enchant == 7) enchantHP="129";
							else if (Enchant == 8) enchantHP="195";
								else if (Enchant == 9) enchantHP="271";
									else if (Enchant >= 10) enchantHP="363";
			}
			else if (CrystalType == 2)
			{
				if (Enchant == 4) enchantHP="18";
				else if (Enchant == 5) enchantHP="54";
					else if (Enchant == 6) enchantHP="106";
						else if (Enchant == 7) enchantHP="177";
							else if (Enchant == 8) enchantHP="267";
								else if (Enchant == 9) enchantHP="373";
									else if (Enchant >= 10) enchantHP="498";
			}
			else if (CrystalType == 3)
			{
				if (Enchant == 4) enchantHP="21";
				else if (Enchant == 5) enchantHP="63";
					else if (Enchant == 6) enchantHP="126";
						else if (Enchant == 7) enchantHP="208";
							else if (Enchant == 8) enchantHP="313";
								else if (Enchant == 9) enchantHP="439";
									else if (Enchant >= 10) enchantHP="585";
			}
			else if (CrystalType == 4)
			{
				if (Enchant == 4) enchantHP="24";
				else if (Enchant == 5) enchantHP="70";
					else if (Enchant == 6) enchantHP="141";
						else if (Enchant == 7) enchantHP="235";
							else if (Enchant == 8) enchantHP="352";
								else if (Enchant == 9) enchantHP="493";
									else if (Enchant >= 10) enchantHP="658";
			}
			else if ((CrystalType == 5) || (CrystalType == 6) || (CrystalType == 7))
			{
				if (Enchant == 4) enchantHP="25";
				else if (Enchant == 5) enchantHP="78";
					else if (Enchant == 6) enchantHP="156";
						else if (Enchant == 7) enchantHP="259";
							else if (Enchant == 8) enchantHP="388";
								else if (Enchant == 9) enchantHP="544";
									else if (Enchant >= 10) enchantHP="726";
			}
		}
	}
	else if (SlotBitType == 4194304)
	{
		if (Enchant == 4) enchantHP="4";
		else if (Enchant == 5) enchantHP="8";
			else if (Enchant >= 6) enchantHP="16";
	}

	return enchantHP;
}

/////////////////////////////////////////////////////////////////////////////////
// INVENTORY Etc
function ReturnTooltip_NTT_ITEM(string param, String TooltipType, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	
	local EItemType eItemType;
	local EEtcItemType eEtcItemType;
	
	local bool bLargeWidth;
	local string SlotString;
	local string strTmp;
	local string strSell;
	local string strCry;
	local int nTmp;
	local int idx;
	
	//제련효과
	local string ItemName;
	local int Quality;
	local int ColorR;
	local int ColorG;
	local int ColorB;
	local string strDesc1;
	local string strDesc2;
	local string strDesc3;
	
	// 보급형 아이템
	local int ItemNameClass;
	
	//셋트아이템
	//~ local array<ItemID> arrItemID;
	local int SetID;
	//~ local int SetID2;
	
	//아데나읽어주기
	local string strAdena;
	local string strAdenaComma;
	local color	 AdenaColor;
	
	local ItemID tmpItemID;
	local UserInfo PlayerInfo;

	if (eSourceType == NTST_ITEM)
	{
		ParamToItemInfo(param, Item);
		
		eItemType = EItemType(Item.ItemType);
		eEtcItemType = EEtcItemType(Item.ItemSubType);
		
		//아이콘 표시
		if (TooltipType == "InventoryWithIcon")
		{
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.u_nTextureWidth = 32;
			m_Info.u_nTextureHeight = 32;
			m_Info.u_strTexture = Item.IconName;
			EndItem();
			
			AddTooltipItemBlank(4);
		}
		
		//아이템 이름 취득
		ItemName = class'UIDATA_ITEM'.static.GetRefineryItemName( Item.Name, Item.RefineryOp1, Item.RefineryOp2 );
		
		ItemNameClass = class'UIDATA_ITEM'.static.GetItemNameClass( Item.ID);
		
		//branch
		AddPrimeItemSymbol(Item);
		//end of branch
		
		//인첸트 ex) "+10"
		if (TooltipType != "InventoryPrice1HideEnchant"
			&& TooltipType != "InventoryPrice1HideEnchantStackable")
			AddTooltipItemEnchant(Item);
		
		//아이템 이름
		AddTooltipItemName(ItemName, Item, ItemNameClass);
		
		//Grade Mark
		AddTooltipItemGrade(Item);
		
		//아이템 갯수
		if (TooltipType != "InventoryPrice1HideEnchantStackable")
		{
			// 2009 10. 15
			// 퀘스트 보상 아이템 타입이면 아이템 개수를 표기 하지 않는다.
			if (TooltipType != "QuestReward")
			{
				AddTooltipItemCount(Item);
			}
		}
			
		//아이템이 아데나면, 읽어주기 스트링
		if (IsAdena(Item.ID))
		{
			//SimpleTooltip을 읽어주기스트링까지 보여준다.
			m_Tooltip.SimpleLineCount = 2;
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_strText = "(" $ ConvertNumToText(Int64ToString(Item.ItemNum)) $ ")";
			EndItem();
		}
		
		//InventoryPrice1 타입
		if (TooltipType == "InventoryPrice1"
			|| TooltipType == "InventoryPrice1HideEnchant"
			|| TooltipType == "InventoryPrice1HideEnchantStackable")
		{
			strAdena = Int64ToString(Item.Price);
			strAdenaComma = MakeCostString(strAdena);
			AdenaColor = GetNumericColor(strAdenaComma);
			
			//가격 : xxx,xxx,xxx
			AddTooltipItemOption(322, strAdenaComma $ " ", true, true, false);
			SetTooltipItemColor(AdenaColor.R, AdenaColor.G, AdenaColor.B, 0);
			
			//"아데나"
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color = AdenaColor;
			m_Info.t_ID= 469;
			EndItem();
			
			//SimpleTooltip을 가격까지 보여준다.
			m_Tooltip.SimpleLineCount = 2;
			
			//읽어주기 스트링
			if (Item.Price>IntToInt64(0))
			{
				m_Tooltip.SimpleLineCount = 3;
				AddTooltipItemOption(0, "(" $ ConvertNumToText(strAdena) $ ")", false, true, false);
				SetTooltipItemColor(AdenaColor.R, AdenaColor.G, AdenaColor.B, 0);
			}
		}
		
		//InventoryPrice2 타입
		if (TooltipType == "InventoryPrice2"
			|| TooltipType == "InventoryPrice2PrivateShop")
		{
			strAdena = Int64ToString(Item.Price);
			strAdenaComma = MakeCostString(strAdena);
			AdenaColor = GetNumericColor(strAdenaComma);
			
			//가격 : 1개당
			AddTooltipItemOption2(322, 468, true, true, false);
			SetTooltipItemColor(AdenaColor.R, AdenaColor.G, AdenaColor.B, 0);
			
			//"xxx,xxx,xxx "
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color = AdenaColor;
			m_Info.t_strText = " " $ strAdenaComma $ " ";
			EndItem();
			
			//"아데나"
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color = AdenaColor;
			m_Info.t_ID= 469;
			EndItem();
			
			//SimpleTooltip을 가격까지 보여준다.
			m_Tooltip.SimpleLineCount = 2;
			
			//읽어주기 스트링
			if (Item.Price>IntToInt64(0))
			{
				m_Tooltip.SimpleLineCount = 3;
				
				//"("
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.nOffSetY = 6;
				m_Info.bLineBreak = true;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_color = AdenaColor;
				m_Info.t_strText = "(";
				EndItem();
				
				//"1개당"
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.nOffSetY = 6;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_color = AdenaColor;
				m_Info.t_ID = 468;
				EndItem();
				
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.nOffSetY = 6;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_color = AdenaColor;
				m_Info.t_strText = " " $ ConvertNumToText(strAdena) $ ")";
				EndItem();
			}
		}
		
		//InventoryPrice2PrivateShop 타입
		if (TooltipType == "InventoryPrice2PrivateShop")
		{
			if (IsStackableItem(Item.ConsumeType) && Item.Reserved64 > IntToInt64(0))
			{
				//"구매개수 : xx"
				AddTooltipItemOption(808, Int64ToString(Item.Reserved64), true, true, false);
			}
		}

		/////////////////////////////////////////////////////////////////////////////////////////
		// 아이템에 따른 각종 정보
		
		SlotString = GetSlotTypeString(Item.ItemType, Item.SlotBitType, Item.ArmorType);
		
		switch (eItemType)
		{
			
		// 1. WEAPON
		case ITEM_WEAPON:
			bLargeWidth = true;
			
			//Slot Type
			strTmp = GetWeaponTypeString(Item.WeaponType);
			if (Len(strTmp)>0)
			{
				AddTooltipItemOption(0, strTmp $ " / " $ SlotString, false, true, false);
			}
			
			//빈공간
			AddTooltipItemBlank(12);
			
			//"[무기 제원]"
			AddTooltipItemOption(1489, "", true, false, false);
			SetTooltipItemColor(255, 255, 255, 0);
			
			//Physical Damage
			if (Item.Enchanted > 0)
			{
				AddTooltipItemOption(94, String(GetPhysicalDamage(Item.WeaponType, Item.SlotBitType, Item.CrystalType, Item.Enchanted, Item.PhysicalDamage)) @ "(" $ string(Item.PhysicalDamage) $ "+" $ string(GetPhysicalDamage(Item.WeaponType, Item.SlotBitType, Item.CrystalType, Item.Enchanted, Item.PhysicalDamage) - Item.PhysicalDamage) $ ")", true, true, false);
				AddTooltipItemOption(98, String(GetMagicalDamage(Item.WeaponType, Item.SlotBitType, Item.CrystalType, Item.Enchanted, Item.MagicalDamage)) @ "(" $ string(Item.MagicalDamage) $ "+" $ string(GetMagicalDamage(Item.WeaponType, Item.SlotBitType, Item.CrystalType, Item.Enchanted, Item.MagicalDamage) - Item.MagicalDamage) $ ")", true, true, false);
			}	
			else
			{
				AddTooltipItemOption(94, String(GetPhysicalDamage(Item.WeaponType, Item.SlotBitType, Item.CrystalType, Item.Enchanted, Item.PhysicalDamage)), true, true, false);
				AddTooltipItemOption(98, String(GetMagicalDamage(Item.WeaponType, Item.SlotBitType, Item.CrystalType, Item.Enchanted, Item.MagicalDamage)), true, true, false);
			}
			//Masical Damage
		//	AddTooltipItemOption(98, String(GetMagicalDamage(Item.WeaponType, Item.SlotBitType, Item.CrystalType, Item.Enchanted, Item.MagicalDamage)), true, true, false);
			
			//Attack Speed
			AddTooltipItemOption(111, GetAttackSpeedString(Item.AttackSpeed), true, true, false);
			
			//SoulShot Count
			if (Item.SoulshotCount>0)
			{
				AddTooltipItemOption(404, "X " $ Item.SoulshotCount, true, true, false);
			}
			
			//SpiritShot Count
			if (Item.SpiritShotCount>0)
			{
				AddTooltipItemOption(496, "X " $ Item.SpiritshotCount, true, true, false);
			}
			
			//Weight
			if (Item.Weight==0)
				AddTooltipItemOption(52, " 0 ", true, true, false);
			else
				AddTooltipItemOption(52, String(Item.Weight), true, true, false);
			//AddTooltipItemOption(52, String(Item.Weight), true, true, false);
			
			//MP Consume
			if (Item.MpConsume != 0)
			{
				AddTooltipItemOption(320, String(Item.MpConsume), true, true, false);
			}
			
			//제련효과
			if (Item.RefineryOp1 != 0 || Item.RefineryOp2 != 0)
			{
				//빈공간
				AddTooltipItemBlank(12);
				
				//"[제련효과]"
				AddTooltipItemOption(1490, "", true, false, false);
				SetTooltipItemColor(255, 255, 255, 0);
				
				//컬러값 취득
				if (Item.RefineryOp2 != 0)
				{
					Quality = class'UIDATA_REFINERYOPTION'.static.GetQuality( Item.RefineryOp2 );
					GetRefineryColor(Quality, ColorR, ColorG, ColorB);
				}
				
				if (Item.RefineryOp1 != 0)
				{
					strDesc1 = "";
					strDesc2 = "";
					strDesc3 = "";
					if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.RefineryOp1, strDesc1, strDesc2, strDesc3 ))
					{	
						if (Len(strDesc1)>0)
						{
							AddTooltipItemOption(0, strDesc1, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc2)>0)
						{
							AddTooltipItemOption(0, strDesc2, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc3)>0)
						{
							AddTooltipItemOption(0, strDesc3, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
					}
				}	
				
				if (Item.RefineryOp2 != 0)
				{
					strDesc1 = "";
					strDesc2 = "";
					strDesc3 = "";
					if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.RefineryOp2, strDesc1, strDesc2, strDesc3 ))
					{
						if (Len(strDesc1)>0)
						{
							AddTooltipItemOption(0, strDesc1, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
							
						}
						if (Len(strDesc2)>0)
						{
							AddTooltipItemOption(0, strDesc2, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc3)>0)
						{
							AddTooltipItemOption(0, strDesc3, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
					}
				}
				
				//"교환/드롭 불가"
				AddTooltipItemOption(1491, "", true, false, false);
				SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
				
				//빈공간
				if (Len(Item.Description)>0)
				{				
					AddTooltipItemBlank(12);
				}
			}
		break;
		
		// 2. ARMOR
		case ITEM_ARMOR:
			bLargeWidth = true;
			
			// Sheild
			if ( Item.SlotBitType == 256 && Item.ArmorType == 4 ) // ArmorType == 4 is sigil.. 
			{
				if (Len(SlotString)>0)
					AddTooltipItemOption(0, SlotString, false, true, false);
				if (Item.PhysicalDefense != 0)
					if (Item.Enchanted > 0)
						AddTooltipItemOption(95, String(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)) @ "(" $ String(Item.PhysicalDefense) $ "+" $ string(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)-Item.PhysicalDefense) $ ")", true, true, false);
					else
						AddTooltipItemOption(95, String(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)), true, true, false);		
				
				if (Item.Enchanted >= 4)
				{
					if (CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted)!="")
					{
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.nOffSetX = 0;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color.R = 187;	
						m_Info.t_color.G = 181;
						m_Info.t_color.B = 138;
						m_Info.t_color.A = 255;
						m_Info.t_strText = " " $ "+" $ CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted) @ "hp";
						EndItem();
					}
				}
				
				//Avoid Modify
				if (Item.AvoidModify != 0)
					AddTooltipItemOption(97, String(Item.AvoidModify), true, true, false);
					//Weight
				if (Item.Weight != 0)
					AddTooltipItemOption(52, String(Item.Weight), true, true, false);
			}
			else if (Item.SlotBitType == 256 || Item.SlotBitType == 128)	//SBT_LHAND or SBT_RHAND
			{
				if (Len(SlotString)>0)
					AddTooltipItemOption(0, SlotString, false, true, false);
				//Shield Defense
				//debug("Shield Defense" $ Item.ShieldDefense);
				if (Item.ShieldDefense != 0)
					if (Item.Enchanted > 0)
						AddTooltipItemOption(95, String(GetShieldDefense(Item.CrystalType, Item.Enchanted, Item.ShieldDefense)) @ "(" $ String(Item.ShieldDefense) $ "+" $ string(GetShieldDefense(Item.CrystalType, Item.Enchanted, Item.ShieldDefense)-Item.ShieldDefense) $ ")", true, true, false);
					else
						AddTooltipItemOption(95, String(GetShieldDefense(Item.CrystalType, Item.Enchanted, Item.ShieldDefense)), true, true, false);
				if (Item.Enchanted >= 4)
				{
					if (CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted)!="")
					{
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.nOffSetX = 0;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color.R = 187;	
						m_Info.t_color.G = 181;
						m_Info.t_color.B = 138;
						m_Info.t_color.A = 255;
						m_Info.t_strText = " " $ "+" $ CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted) @ "hp";
						EndItem();
					}
				}				
				//Shield Defense Rate
				//debug("Shield Defense Rate" $ Item.ShieldDefenseRate);
				if (Item.ShieldDefenseRate != 0)
				AddTooltipItemOption(317, String(Item.ShieldDefenseRate), true, true, false);
				
				//Avoid Modify
				//debug("Avoid Modify" $ Item.AvoidModify);
				if (Item.AvoidModify != 0)
				AddTooltipItemOption(97, String(Item.AvoidModify), true, true, false);
				
				//Weight
				//debug("Weight" $ Item.Weight);
				if (Item.Weight != 0)
				AddTooltipItemOption(52, String(Item.Weight), true, true, false);
			}
			
			// Magical Armor
			else if (IsMagicalArmor(Item.ID))
			{
				//Slot Type
				if (Len(SlotString)>0)
					AddTooltipItemOption(0, SlotString, false, true, false);
				
				//MP Bonus
				AddTooltipItemOption(388, String(Item.MpBonus), true, true, false);
				
				//Physical Defense
				if (Item.SlotBitType == 65536)
				{
					
				}
				else if (  Item.SlotBitType == 524288)
				{
				}
				else if ( Item.SlotBitType == 262144)
				{
				}
				else
				{
					if (Item.PhysicalDefense != 0)
						if (Item.Enchanted > 0)
							AddTooltipItemOption(95, String(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)) @ "(" $ String(Item.PhysicalDefense) $ "+" $ string(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)-Item.PhysicalDefense) $ ")", true, true, false);
						else
							AddTooltipItemOption(95, String(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)), true, true, false);	
				}
					
				if (Item.Enchanted >= 4)
				{
					if (CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted)!="")
					{
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.nOffSetX = 0;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color.R = 187;	
						m_Info.t_color.G = 181;
						m_Info.t_color.B = 138;
						m_Info.t_color.A = 255;
						m_Info.t_strText = " " $ "+" $ CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted) @ "hp";
						EndItem();
					}
				}
				//Weight
					if (Item.Weight != 0)
					AddTooltipItemOption(52, String(Item.Weight), true, true, false);
			}
			
			// Physical Armor
			else
			{
				//Slot Type
				//debug("Physical Armor1 " $ Item.SlotBitType $ Item.PhysicalDefense);
				if (Len(SlotString)>0)
					AddTooltipItemOption(0, SlotString, false, true, false);
				
				//Physical Defense
				if (Item.SlotBitType == 65536)
				{
					
				}
				else if (  Item.SlotBitType == 524288)
				{
				}
				else if ( Item.SlotBitType == 262144)
				{
				}
				else
				{
					if (Item.PhysicalDefense != 0)
						if (Item.Enchanted > 0)
							AddTooltipItemOption(95, String(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)) @ "(" $ String(Item.PhysicalDefense) $ "+" $ string(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)-Item.PhysicalDefense) $ ")", true, true, false);
						else
							AddTooltipItemOption(95, String(GetPhysicalDefense(Item.CrystalType, Item.Enchanted, Item.PhysicalDefense)), true, true, false);	
				}
				
				
				if (Item.Enchanted >= 4)
				{
					if (CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted)!="")
					{
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.nOffSetX = 0;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color.R = 187;	
						m_Info.t_color.G = 181;
						m_Info.t_color.B = 138;
						m_Info.t_color.A = 255;
						m_Info.t_strText = " " $ "+" $ CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted) @ "hp";
						EndItem();
					}
				}
				
				
				//Weight
				if (Item.Weight != 0)
				AddTooltipItemOption(52, String(Item.Weight), true, true, false);
			}
			//제련효과
			if (Item.RefineryOp1 != 0 || Item.RefineryOp2 != 0)
			{
				//빈공간
				AddTooltipItemBlank(12);
				
				//"[제련효과]"
				AddTooltipItemOption(1490, "", true, false, false);
				SetTooltipItemColor(255, 255, 255, 0);
				
				//컬러값 취득
				if (Item.RefineryOp2 != 0)
				{
					Quality = class'UIDATA_REFINERYOPTION'.static.GetQuality( Item.RefineryOp2 );
					GetRefineryColor(Quality, ColorR, ColorG, ColorB);
				}
				
				if (Item.RefineryOp1 != 0)
				{
					strDesc1 = "";
					strDesc2 = "";
					strDesc3 = "";
					if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.RefineryOp1, strDesc1, strDesc2, strDesc3 ))
					{	
						if (Len(strDesc1)>0)
						{
							AddTooltipItemOption(0, strDesc1, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc2)>0)
						{
							AddTooltipItemOption(0, strDesc2, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc3)>0)
						{
							AddTooltipItemOption(0, strDesc3, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
					}
				}	
				
				if (Item.RefineryOp2 != 0)
				{
					strDesc1 = "";
					strDesc2 = "";
					strDesc3 = "";
					if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.RefineryOp2, strDesc1, strDesc2, strDesc3 ))
					{
						if (Len(strDesc1)>0)
						{
							AddTooltipItemOption(0, strDesc1, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
							
						}
						if (Len(strDesc2)>0)
						{
							AddTooltipItemOption(0, strDesc2, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc3)>0)
						{
							AddTooltipItemOption(0, strDesc3, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
					}
				}
				//빈공간
				AddTooltipItemBlank(12);
			}
			
		break;
		
		// 3. ACCESSARY
		case ITEM_ACCESSARY:
			bLargeWidth = true;
			
			//Slot Type
			if (Len(SlotString)>0)
			{
				if (Item.SlotBitType == 4194304 )
				{
					if (IsPassiveTalisman(Item.ID.ClassID)) AddTooltipItemOption(0, "Passive Talisman", false, true, false);
					else  AddTooltipItemOption(0, "Active Talisman", false, true, false);
				}
				else AddTooltipItemOption(0, SlotString, false, true, false);
			}
			//Magical Defense
			// 탈리스만은 아이템 마방을 보여주지 않는다.
			// 왼팔찌 오른팔찌도 마방을 보여주지 않는다. 
			if ((Item.SlotBitType != 4194304 ) && (Item.SlotBitType != 1048576 ) && (Item.SlotBitType != 2097152 ))
				if (Item.Enchanted > 0)
					AddTooltipItemOption(99, String(GetMagicalDefense(Item.CrystalType, Item.Enchanted, Item.MagicalDefense)) @ "(" $ String(Item.MagicalDefense) $ "+" $ string(GetMagicalDefense(Item.CrystalType, Item.Enchanted, Item.MagicalDefense)-Item.MagicalDefense) $ ")", true, true, false);
				else
					AddTooltipItemOption(99, String(GetMagicalDefense(Item.CrystalType, Item.Enchanted, Item.MagicalDefense)), true, true, false);	
			
			if (Item.Weight == 0)
				AddTooltipItemOption(52, " 0 ", true, true, false);
			else 
				AddTooltipItemOption(52, String(Item.Weight), true, true, false);
			
			//debug ("Refinery Result Accessotires" @ Item.RefineryOp1);
			//제련효과
			if (Item.RefineryOp1 != 0 || Item.RefineryOp2 != 0)
			{
				//빈공간
				AddTooltipItemBlank(12);
				
				//"[제련효과]"
				AddTooltipItemOption(1490, "", true, false, false);
				SetTooltipItemColor(255, 255, 255, 0);
				
				//컬러값 취득
				if (Item.RefineryOp2 != 0)
				{
					Quality = class'UIDATA_REFINERYOPTION'.static.GetQuality( Item.RefineryOp2 );
					GetRefineryColor(Quality, ColorR, ColorG, ColorB);
				}
				
				if (Item.RefineryOp1 != 0)
				{
					strDesc1 = "";
					strDesc2 = "";
					strDesc3 = "";
					if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.RefineryOp1, strDesc1, strDesc2, strDesc3 ))
					{	
						if (Len(strDesc1)>0)
						{
							AddTooltipItemOption(0, strDesc1, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc2)>0)
						{
							AddTooltipItemOption(0, strDesc2, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc3)>0)
						{
							AddTooltipItemOption(0, strDesc3, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
					}
				}	
				
				if (Item.RefineryOp2 != 0)
				{
					strDesc1 = "";
					strDesc2 = "";
					strDesc3 = "";
					if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.RefineryOp2, strDesc1, strDesc2, strDesc3 ))
					{
						if (Len(strDesc1)>0)
						{
							AddTooltipItemOption(0, strDesc1, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
							
						}
						if (Len(strDesc2)>0)
						{
							AddTooltipItemOption(0, strDesc2, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
						if (Len(strDesc3)>0)
						{
							AddTooltipItemOption(0, strDesc3, false, true, false);
							SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						}
					}
				}
				//빈공간
				AddTooltipItemBlank(12);
			}
			
			
				
		break;
		
		// 4. QUEST
		case ITEM_QUESTITEM:
			bLargeWidth = true;
			
			//Slot Type
			if (Len(SlotString)>0)
				AddTooltipItemOption(0, SlotString, false, true, false);
		break;
		
		// 5. ETC
		case ITEM_ETCITEM:
			bLargeWidth = true;
			
			if (eEtcItemType == ITEME_PET_COLLAR)
			{
				//Pet Name
				if (Item.Damaged == 0)
					nTmp = 971;
				else
					nTmp = 970;
				AddTooltipItemOption2(969, nTmp, true, true, false);
				
				//Pet Level
				AddTooltipItemOption(88, String(Item.Enchanted), true, true, false);
			}
			else if (eEtcItemType == ITEME_TICKET_OF_LORD)
			{
				AddTooltipItemOption(972, String(Item.Enchanted), true, true, false);
			}
			else if (eEtcItemType == ITEME_LOTTO)
			{
				// 복권에서는 bless가 회차, 몬스터레이스에서는 Enchant가 회차입니다. 주의하세요! - lancelot 2008. 11. 11.
				// 회차
				AddTooltipItemOption(670, String(Item.Blessed), true, true, false);
				
				// 선택번호
				AddTooltipItemOption(671, GetLottoString(Item.Enchanted, Item.Damaged), true, true, false);
			}
			else if (eEtcItemType == ITEME_RACE_TICKET)
			{
				// 회차
				AddTooltipItemOption(670, String(Item.Enchanted), true, true, false);
				
				// 선택번호
				AddTooltipItemOption(671, GetRaceTicketString(Item.Blessed), true, true, false);
				
				//Money
				AddTooltipItemOption(744, String(Item.Damaged*100), true, true, false);
			}
			//Weight
			//~ if (Item.Price!=0)
			if (Item.Weight==0)
				AddTooltipItemOption(52, " 0 ", true, true, false);
			else
				AddTooltipItemOption(52, String(Item.Weight), true, true, false);
		break;
		
		}
		/////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////////
		
		// [칠월칠석, 방어구 각인] item enchant option - by jin 09/08/05
		if (Item.EnchantOption1 != 0 || Item.EnchantOption2 != 0 || Item.EnchantOption3 != 0)
		{
			//빈공간
			AddTooltipItemBlank(12);
			
			//"[인챈트효과]"
			AddTooltipItemOption(2214, "", true, false, false);
			SetTooltipItemColor(255, 255, 255, 0);
			
			if (Item.Enchanted >= 4)
			{
				if (CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted)!="")
				{
					StartItem();
					m_Info.eType = DIT_TEXT;
					m_Info.nOffSetY = 6;
					m_Info.nOffSetX = 0;
					m_Info.t_bDrawOneLine = true;
					m_Info.t_color.R = 187;	
					m_Info.t_color.G = 181;
					m_Info.t_color.B = 138;
					m_Info.t_color.A = 255;
					m_Info.t_strText = " " $ "+" $ CheckHPEnchant(Item.SlotBitType,Item.CrystalType,Item.Enchanted) @ "hp";
					EndItem();
				}
			}		
			SetTooltipItemColor(187, 181, 138, 0);
			
			//컬러값 취득
			if (Item.EnchantOption1 != 0)
			{
				// [칠월칠석, 방어구각인] 각인 효과는 일단 무조건 1번 색상을 사용. - by jin 09/08/06
				GetRefineryColor(1, ColorR, ColorG, ColorB);
			}
			
			if (Item.EnchantOption1 != 0)
			{
				strDesc1 = "";
				strDesc2 = "";
				strDesc3 = "";
				if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.EnchantOption1, strDesc1, strDesc2, strDesc3 ))
				{	
					if (Len(strDesc1)>0)
					{
						AddTooltipItemOption(0, strDesc1, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
					}
					if (Len(strDesc2)>0)
					{
						AddTooltipItemOption(0, strDesc2, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
					}
					if (Len(strDesc3)>0)
					{
						AddTooltipItemOption(0, strDesc3, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
					}
				}
			}	
			
			if (Item.EnchantOption2 != 0)
			{
				strDesc1 = "";
				strDesc2 = "";
				strDesc3 = "";
				if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.EnchantOption2, strDesc1, strDesc2, strDesc3 ))
				{
					if (Len(strDesc1)>0)
					{
						AddTooltipItemOption(0, strDesc1, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						
					}
					if (Len(strDesc2)>0)
					{
						AddTooltipItemOption(0, strDesc2, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
					}
					if (Len(strDesc3)>0)
					{
						AddTooltipItemOption(0, strDesc3, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
					}
				}
			}

			if (Item.EnchantOption3 != 0)
			{
				strDesc1 = "";
				strDesc2 = "";
				strDesc3 = "";
				if (class'UIDATA_REFINERYOPTION'.static.GetOptionDescription( Item.EnchantOption3, strDesc1, strDesc2, strDesc3 ))
				{
					if (Len(strDesc1)>0)
					{
						AddTooltipItemOption(0, strDesc1, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
						
					}
					if (Len(strDesc2)>0)
					{
						AddTooltipItemOption(0, strDesc2, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
					}
					if (Len(strDesc3)>0)
					{
						AddTooltipItemOption(0, strDesc3, false, true, false);
						SetTooltipItemColor(ColorR, ColorG, ColorB, 0);
					}
				}
			}

			//빈공간
			AddTooltipItemBlank(12);
		}
		

		//내구도 아이템
		if (Item.CurrentDurability >= 0 && Item.Durability > 0)
		{
			bLargeWidth = true;
			
			//빈공간
			AddTooltipItemBlank(12);
			
			//<투영 병기 정보>
			AddTooltipItemOption(1492, "", true, false, false);
			SetTooltipItemColor(255, 255, 255, 0);
			
			//사용가능 시간
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color = Gray;
			m_Info.t_ID = 1493;
			EndItem();
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			if (TooltipType != "TaliList")
			{
				if (Item.CurrentDurability+1 <= CheckDurability(Item.ID.ClassID))
				{
					m_Info.t_color = Red;
				}
				else
				if (Item.CurrentDurability+1 <= CheckDurability(Item.ID.ClassID)+5)
				{
					m_Info.t_color = Yellow;
				}
				else
				{
					m_Info.t_color = Bronze;
				}
			}
			else
			{
				if (Item.CurrentDurability+1 <= CheckDurability(GetTalismanItemID(Item.ID.ClassID)))
				{
					m_Info.t_color = Red;
				}
				else
				if (Item.CurrentDurability+1 <= CheckDurability(GetTalismanItemID(Item.ID.ClassID))+5)
				{
					m_Info.t_color = Yellow;
				}
				else
				{
					m_Info.t_color = Bronze;
				}
			}
			m_Info.t_strText = " " $ Item.CurrentDurability $ "/" $ Item.Durability;
			EndItem();
			
			
			if (Item.CurrentDurability >= 0 && Item.Durability > 0)
				if (TooltipType == "TaliList")
				{
					if (Item.ID.ClassID == 3436)
					{
						bLargeWidth = true;
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.bLineBreak = true;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color = Gray;
						m_Info.t_strText = "HP Cost: ";
						EndItem();
						
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color = Bronze;
						m_Info.t_strText = "800";
						EndItem();
					}
					if (GetTalismanItemID(Item.ID.ClassID) != -1)
					{
						bLargeWidth = true;
						
						//AddTooltipItemBlank(12);
										
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.bLineBreak = true;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color = Gray;
						m_Info.t_strText = "Requires Item Mana: ";
						EndItem();
						
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color = Bronze;
						m_Info.t_strText = string(CheckDurability(GetTalismanItemID(Item.ID.ClassID)));
						EndItem();
					}
				}
				else
				{
					if (!IsPassiveTalisman(Item.ID.ClassID) && (GetTalismanSkillID(Item.ID.ClassID) != -1))
					{					
						bLargeWidth = true;
					
						//AddTooltipItemBlank(12);
						if (Item.ID.ClassID == 10423)
						{
							bLargeWidth = true;
							StartItem();
							m_Info.eType = DIT_TEXT;
							m_Info.nOffSetY = 6;
							m_Info.bLineBreak = true;
							m_Info.t_bDrawOneLine = true;
							m_Info.t_color = Gray;
							m_Info.t_strText = "HP Cost: ";
							EndItem();
							
							StartItem();
							m_Info.eType = DIT_TEXT;
							m_Info.nOffSetY = 6;
							m_Info.t_bDrawOneLine = true;
							m_Info.t_color = Bronze;
							m_Info.t_strText = "800";
							EndItem();
						}
						
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.bLineBreak = true;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color = Gray;
						m_Info.t_strText = "Requires Item Mana: ";
						EndItem();
						
						StartItem();
						m_Info.eType = DIT_TEXT;
						m_Info.nOffSetY = 6;
						m_Info.t_bDrawOneLine = true;
						m_Info.t_color = Bronze;
						m_Info.t_strText = string(CheckDurability(Item.ID.ClassID));
						EndItem();
					}
				}
			
			//"교환/드롭 불가"
			AddTooltipItemOption(1491, "", true, false, false);
			
			//빈공간
			if (Len(Item.Description)>0)
			{
				AddTooltipItemBlank(12);
			}
		}
		
		//branch
		//에너지 정보
		if (Item.BR_MaxEnergy > 0)
		{
			//bLargeWidth = true;
			
			//빈공간
			AddTooltipItemBlank(12);
			//<에너지 정보>
			AddTooltipItemOption(5065, "", true, false, false);
			SetTooltipItemColor(255, 255, 255, 0);

			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color = Gray;
			m_Info.t_ID = 5066;
			EndItem();
						
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.t_bDrawOneLine = true;
			m_Info.bLineBreak = true;
			if ( Item.BR_CurrentEnergy==0 || (Item.BR_MaxEnergy / Item.BR_CurrentEnergy > 10) )
			{
				m_Info.t_color.R = 255;
				m_Info.t_color.G = 0;
				m_Info.t_color.B = 0;
			}
			else
			{
				m_Info.t_color.R = 176;
				m_Info.t_color.G = 155;
				m_Info.t_color.B = 121;
			}				
				m_Info.t_color.A = 255;
			//m_Info.t_strText = "  " $ Item.BR_CurrentEnergy $ "/" $ Item.BR_MaxEnergy;
			m_Info.t_strText = " " ;
			ParamAdd(m_Info.Condition, "Type", "CurrentEnergy");
			EndItem();
		}
		//end of branch
			
		//설명
		//ParseString(Item.ID.ClassID, "ClassID", strTmp);
		ParseString(Item.Description, "Cry", strCry);
		ParseString(Item.Description, "Sell", strSell);
		if (Len(Item.Description)>(Len(strCry) + 5 + Len(strSell) + 5))
		{
			bLargeWidth = true;
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			if (Len(strSell)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strSell) -5);
			if (Len(strCry)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strCry) - 5);
		//	if (Len(strTmp)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strTmp) - 4);
		//	Item.Description = Left(Item.Description,Len(Item.Description) - Len(strTmp)  - Len(strCry) -  Len(strSell) );
			m_Info.t_strText = Item.Description;
			EndItem();
		}
		
		AddTooltipItemBlank(8);
		
		// Mostra o ID do item somente para GM (nUserRank > 0).
		GetPlayerInfo(PlayerInfo);
		if (TooltipType != "TaliList" && PlayerInfo.nUserRank > 0)
		{
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.nOffSetX = 2;
			m_Info.nOffSetY = 1;
			
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.u_nTextureWidth = 16;
			m_Info.u_nTextureHeight = 16;
			m_Info.u_nTextureUWidth = 16;
			m_Info.u_nTextureUHeight = 16;
			m_Info.u_strTexture = "MonIcaTex.itemid";
			EndItem();
			
			StartItem();
			m_Info.nOffSetX = 4;
			m_Info.nOffSetY = 3;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.eType = DIT_TEXT;
			m_Info.t_strText = MakeCostString(string(Item.ID.ClassID));
			EndItem();
		}
		
		if (Len(strCry)>0)
		{
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.nOffSetX = 2;
			m_Info.nOffSetY = 6;
		
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.u_nTextureWidth = 16;
			m_Info.u_nTextureHeight = 16;
			m_Info.u_nTextureUWidth = 32;
			m_Info.u_nTextureUHeight = 32;
			
			if ((item.CrystalType == 5) || (item.CrystalType == 6) || (item.CrystalType == 7))
				m_Info.u_strTexture = "icon.etc_crystal_gold_i00";
			else if (item.CrystalType == 4)
					m_Info.u_strTexture = "icon.etc_crystal_silver_i00";
				else if (item.CrystalType == 3)
					m_Info.u_strTexture = "icon.etc_crystal_red_i00";
					else if (item.CrystalType == 2)
						m_Info.u_strTexture = "icon.etc_crystal_green_i00";
						else if (item.CrystalType == 1)
							m_Info.u_strTexture = "icon.etc_crystal_blue_i00";
			EndItem();
			
			StartItem();
			m_Info.nOffSetX = 4;
			m_Info.nOffSetY = 8;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.eType = DIT_TEXT;
			m_Info.t_strText = MakeCostString(strCry);
			EndItem();
		}
		
		if (Len(strSell)>0)
		{
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.nOffSetX = 1;
			m_Info.nOffSetY = 4;	
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.u_nTextureWidth = 18;
			m_Info.u_nTextureHeight = 13;
			m_Info.u_strTexture = "L2UI_CT1.Icon_DF_Common_Adena";
			EndItem();
			
			StartItem();
			m_Info.nOffSetX = 3;
			m_Info.nOffSetY = 6;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.eType = DIT_TEXT; 
			m_Info.t_strText = MakeCostString(strSell);
			EndItem();
		}
		
			
		/////////////////////////////////////////////////////////////////////////////////////////
		// 셋트 아이템 정보
		if (IsValidItemID(Item.ID))
		{
			for (idx=0; idx<TOOLTIP_SETITEM_MAX; idx++)
			{
				//셋트아이템 리스트
				
				for (SetID=0; SetID<class'UIDATA_ITEM'.static.GetSetItemNum(Item.ID, idx); SetID++) //0,1,2번 세트아이템효과 에 대해서 각각 몇가지의 세트가 완비되야하나..
				{ 					
					bLargeWidth = true;
					if (!class'UIDATA_ITEM'.static.IsExistSetItem(Item.ID, idx, SetID))
					{					
						tmpItemID.classID = class'UIDATA_ITEM'.static.GetSetItemFirstID(Item.ID, idx, SetID);
				
						if (tmpItemID.classID > 0)
						{
							strTmp = class'UIDATA_ITEM'.static.GetItemName(tmpItemID);
							StartItem();
							m_Info.eType = DIT_TEXTURE;
							m_Info.nOffSetX = 2;
							m_Info.nOffSetY = 5;
		
							m_Info.bLineBreak = true;
							m_Info.t_bDrawOneLine = true;
							m_Info.u_nTextureWidth = 16;
							m_Info.u_nTextureHeight = 16;
							m_Info.u_nTextureUWidth = 32;
							m_Info.u_nTextureUHeight = 32;
							m_Info.u_strTexture = class'UIDATA_ITEM'.static.GetItemTextureName(tmpItemID);
							EndItem();
							
							StartItem();
							m_Info.eType = DIT_TEXT;
							m_Info.nOffSetX = 5;
							m_Info.nOffSetY = 7;
					//		m_Info.bLineBreak = true;
							m_Info.t_bDrawOneLine = true;
							m_Info.t_color.R = 112;
							m_Info.t_color.G = 115;
							m_Info.t_color.B = 123;
							m_Info.t_color.A = 255;
							m_Info.t_strText = strTmp;
							ParamAdd(m_info.Condition, "SetItemNum", string(idx));
							ParamAdd(m_Info.Condition, "Type", "Equip");
							ParamAddItemID(m_Info.Condition, Item.ID);						
							ParamAdd(m_Info.Condition, "CurTypeID", string(SetID));		//비교할 아이템의 Type 예(0번:흉갑 1번:각반 2번:헬멧 3번:팔 4번 다리 ..ItemName.txt에 들어있는순서
							ParamAdd(m_Info.Condition, "NormalColor", "112,115,123");
							ParamAdd(m_Info.Condition, "EnableColor", "176,185,205");
							EndItem();
						}
					}
				}			
		
				//셋트효과
				strTmp = class'UIDATA_ITEM'.static.GetSetItemEffectDescription(Item.ID, idx);
				if (Len(strTmp)>0)
				{
					bLargeWidth = true;
					
					StartItem();
					m_Info.eType = DIT_TEXT;
					m_Info.nOffSetY = 6;
					m_Info.bLineBreak = true;
					m_Info.t_color.R = 128;
					m_Info.t_color.G = 127;
					m_Info.t_color.B = 103;
					m_Info.t_color.A = 255;
					m_Info.t_strText = strTmp;
					ParamAdd(m_Info.Condition, "Type", "SetEffect");
					ParamAddItemID(m_Info.Condition, Item.ID);
					ParamAdd(m_Info.Condition, "EffectID", String(idx));
					ParamAdd(m_Info.Condition, "NormalColor", "128,127,103");
					ParamAdd(m_Info.Condition, "EnableColor", "183,178,122");
					EndItem();	
				}
			}
			//인첸트 셋트효과
			strTmp = class'UIDATA_ITEM'.static.GetSetItemEnchantEffectDescription(Item.ID);
			if (Len(strTmp)>0)
			{
				bLargeWidth = true;
				
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.nOffSetY = 6;
				m_Info.bLineBreak = true;
				m_Info.t_color.R = 74;
				m_Info.t_color.G = 92;
				m_Info.t_color.B = 104;
				m_Info.t_color.A = 255;
				m_Info.t_strText = strTmp;
				ParamAdd(m_Info.Condition, "Type", "EnchantEffect");
				ParamAddItemID(m_Info.Condition, Item.ID);
				ParamAdd(m_Info.Condition, "NormalColor", "74,92,104");
				ParamAdd(m_Info.Condition, "EnableColor", "111,146,169");
				EndItem();
			}
		}
		
		//퀘스트 정보를 표시한다.
		if (TooltipType != "TaliList") AddTooltipItemQuestList(Item);
		
		// 속성 게이지를 그려준다. 
		AddTooltipItemAttributeGage(Item);
		
		// 기간제 아이템
		if ( Item.CurrentPeriod > 0)
		{
			//빈공간
			AddTooltipItemBlank(12);
			
			//<기간제 아이템>
			AddTooltipItemOption(1739, "", true, false, false);
			SetTooltipItemColor(255, 255, 255, 0);
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 163;
			m_Info.t_color.G = 163;
			m_Info.t_color.B = 163;
			m_Info.t_color.A = 255;
			m_Info.t_ID = 1199;
			EndItem();
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 163;
			m_Info.t_color.G = 163;
			m_Info.t_color.B = 163;
			m_Info.t_color.A = 255;
			m_Info.t_strText = " : ";
			EndItem();
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.t_strText = "" $ MakeTimeStr(Item.CurrentPeriod);
			ParamAdd(m_Info.Condition, "Type", "PeriodTime");
			EndItem();
		}
	}
	else
	{
		return;
	}
	
	if (bLargeWidth)
		m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;

	if(TooltipType == "InventoryPawnViewer")				// PawnViewer용 추가 - lancelot 2007. 10. 16.
	{
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.nOffSetY = 6;
		m_Info.t_bDrawOneLine = true;
		m_Info.bLineBreak = true;
		m_Info.t_strText ="ID : "$string(Item.Id.classID);
		EndItem();
	}

	ReturnTooltipInfo(m_Tooltip);
}


/////////////////////////////////////////////////////////////////////////////////
// ACTION
function ReturnTooltip_NTT_ACTION(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	local MacroInfo mInfo;
	local ItemID MacroID;
	local bool isMacro;
	local int idx;
	
	isMacro = false;
	
	if (eSourceType == NTST_ITEM)
	{
		ParseString( param, "Name", Item.Name);
		ParseString( param, "Description", Item.Description);
		
		//액션 이름
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_strText = Item.Name;
		EndItem();
		
		ParseInt( param, "ClassID", MacroID.ClassID);
		class'UIDATA_MACRO'.static.GetMacroInfo( MacroID, mInfo );	
		
		for ( idx = 0; idx < 12; idx++ )
		{
			if ( mInfo.CommandList[idx] != "" )
			{
				m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
				isMacro = true;
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.nOffSetY = 6;
				m_Info.t_bDrawOneLine = false;
				m_Info.bLineBreak = true;
				m_Info.t_color.R = 123;
				m_Info.t_color.G = 63;
				m_Info.t_color.B = 178;
				m_Info.t_color.A = 255;
				m_Info.t_strText = mInfo.CommandList[idx];
				EndItem();
			}
		}
		
		//액션 설명
		if ((Len(Item.Description)>0) && (!isMacro))
		{
			m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = false;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Item.Description;
			EndItem();
		}		
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// SKILL
function ReturnTooltip_NTT_SKILL(string param, ETooltipSourceType eSourceType)
{
	
	local ItemInfo Item;
	
	local EItemParamType eItemParamType;
	local EShortCutItemType eShortCutType;
	local int nTmp;
	local int SkillLevel;
	
	debug("툴팁들어오나");
	if (eSourceType == NTST_ITEM)
	{
		ParseItemID( param, Item.ID );
		ParseString( param, "Name", Item.Name);
		ParseString( param, "AdditionalName", Item.AdditionalName);
		ParseString( param, "Description", Item.Description);
		ParseInt( param, "Level", Item.Level);
		
		eShortCutType = EShortCutItemType(Item.ItemSubType);
		eItemParamType = EItemParamType(Item.ItemType);
		SkillLevel = Item.Level;
		
		m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
		
		//아이템 이름
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_strText = Item.Name;
		EndItem();
		
		// 인챈트시 변환된 레벨을 원래 레벨로 변환시켜준다. 
		if (Len(Item.AdditionalName)>0)
		{			
			SkillLevel = class'UIDATA_SKILL'.static.GetEnchantSkillLevel( Item.ID, Item.Level );
		}
		
		//ex) " Lv "
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_strText = " ";
		EndItem();
		
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 163;
		m_Info.t_color.G = 163;
		m_Info.t_color.B = 163;
		m_Info.t_color.A = 255;
		m_Info.t_ID = 88;
		EndItem();
		
		//스킬 레빌
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		m_Info.t_strText = " " $ SkillLevel;
		EndItem();
		
		// 인챈트 내용을 뿌려주는 곳은 이곳
		if (Len(Item.AdditionalName)>0)
		{
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetX = 5;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 255;
			m_Info.t_color.G = 217;
			m_Info.t_color.B = 105;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Item.AdditionalName;
			EndItem();
		}
		
		//Operate Type
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.nOffSetY = 6;
		m_Info.bLineBreak = true;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		m_Info.t_strText = class'UIDATA_SKILL'.static.GetOperateType( Item.ID, Item.Level );
		EndItem();
		
		//소모HP
		nTmp = class'UIDATA_SKILL'.static.GetHpConsume( Item.ID, Item.Level );
		if (nTmp>0)
		{
			AddTooltipItemOption(1195, String(nTmp), true, true, false);
		}
		
		//소모MP
		nTmp = class'UIDATA_SKILL'.static.GetMpConsume( Item.ID, Item.Level );
		if (nTmp>0)
		{
			AddTooltipItemOption(320, String(nTmp), true, true, false);
		}
		
		//유효거리
		nTmp = class'UIDATA_SKILL'.static.GetCastRange( Item.ID, Item.Level );
		if (nTmp>=0)
		{
			AddTooltipItemOption(321, String(nTmp), true, true, false);
		}
		
		//설명
		if (Len(Item.Description)>0)
		{
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Item.Description;
			EndItem();	
		}		
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// ABNORMALSTATUS
function ReturnTooltip_NTT_ABNORMALSTATUS(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	local int ShowLevel;
	
	local EItemParamType eItemParamType;
	local EShortCutItemType eShortCutType;
	
	if (eSourceType == NTST_ITEM)
	{
		ParseItemID( param, Item.ID );
		ParseString( param, "Name", Item.Name);
		ParseString( param, "AdditionalName", Item.AdditionalName);
		ParseString( param, "Description", Item.Description);
		ParseInt( param, "Level", Item.Level);
		ParseInt( param, "Reserved", Item.Reserved);
		
		eShortCutType = EShortCutItemType(Item.ItemSubType);
		eItemParamType = EItemParamType(Item.ItemType);
		
		m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
		
		//아이템 이름
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_strText = Item.Name;
		EndItem();
		
		ShowLevel = Item.Level;
		if (Len(Item.AdditionalName)>0)
		{
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetX = 5;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 255;
			m_Info.t_color.G = 217;
			m_Info.t_color.B = 105;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Item.AdditionalName;
			EndItem();
			
			ShowLevel = class'UIDATA_SKILL'.static.GetEnchantSkillLevel( Item.ID, Item.Level );
		}
		
		//ex) " Lv "
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_strText = " ";
		EndItem();
		
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 163;
		m_Info.t_color.G = 163;
		m_Info.t_color.B = 163;
		m_Info.t_color.A = 255;
		m_Info.t_ID = 88;
		EndItem();
		
		//스킬 레벨
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		m_Info.t_strText = " " $ ShowLevel;
		EndItem();
		
		//남은시간
		if (!IsDeBuff(Item.ID, Item.Level) && Item.Reserved>=0)
		{
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 163;
			m_Info.t_color.G = 163;
			m_Info.t_color.B = 163;
			m_Info.t_color.A = 255;
			m_Info.t_ID = 1199;
			EndItem();
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 163;
			m_Info.t_color.G = 163;
			m_Info.t_color.B = 163;
			m_Info.t_color.A = 255;
			m_Info.t_strText = " : ";
			EndItem();
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 176;
			m_Info.t_color.G = 155;
			m_Info.t_color.B = 121;
			m_Info.t_color.A = 255;
			m_Info.t_strText = MakeBuffTimeStr(Item.Reserved);
			ParamAdd(m_Info.Condition, "Type", "RemainTime");
			EndItem();
		}
		
		//설명
		if (Len(Item.Description)>0)
		{
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Item.Description;
			EndItem();	
		}		
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// NORMALITEM
function ReturnTooltip_NTT_NORMALITEM(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	
	if (eSourceType == NTST_ITEM)
	{
		ParseString( param, "Name", Item.Name);
		ParseString( param, "Description", Item.Description);
		ParseString( param, "AdditionalName", Item.AdditionalName);
		ParseInt( param, "CrystalType", Item.CrystalType);
		
		//아이템 이름
		AddTooltipItemName(Item.Name, Item, 1);
		
		//Grade Mark
		AddTooltipItemGrade(Item);
		
		//설명
		if (Len(Item.Description)>0)
		{
			m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Item.Description;
			EndItem();	
		}		
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// RECIPE
function ReturnTooltip_NTT_RECIPE(string param, ETooltipSourceType eSourceType, bool bShowPrice)
{
	local ItemInfo Item;
	
	local string strAdena;
	local string strAdenaComma;
	local color	 AdenaColor;
	
	if (eSourceType == NTST_ITEM)
	{
		ParseString( param, "Name", Item.Name);
		ParseString( param, "Description", Item.Description);
		ParseString( param, "AdditionalName", Item.AdditionalName);
		ParseInt( param, "CrystalType", Item.CrystalType);
		ParseInt( param, "Weight", Item.Weight);
		ParseINT64( param, "Price", Item.Price);
		
		//아이템 이름
		AddTooltipItemName(Item.Name, Item, 1);
		
		//Grade Mark
		AddTooltipItemGrade(Item);
		
		//가격
		if (bShowPrice)
		{
			strAdena = Int64ToString(Item.Price);
			strAdenaComma = MakeCostString(strAdena);
			AdenaColor = GetNumericColor(strAdenaComma);
			
			//가격 : xxx,xxx,xxx
			AddTooltipItemOption(641, strAdenaComma $ " ", true, true, false);
			SetTooltipItemColor(AdenaColor.R, AdenaColor.G, AdenaColor.B, 0);
			
			//"아데나"
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color = AdenaColor;
			m_Info.t_ID= 469;
			EndItem();
			
			//읽어주기 스트링
			AddTooltipItemOption(0, "(" $ ConvertNumToText(strAdena) $ ")", false, true, false);
			SetTooltipItemColor(AdenaColor.R, AdenaColor.G, AdenaColor.B, 0);
		}
		
		//Weight
		AddTooltipItemOption(52, String(Item.Weight), true, true, false);
		
		//설명
		if (Len(Item.Description)>0)
		{
			m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Item.Description;
			EndItem();	
		}		
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// SHORTCUT
function ReturnTooltip_NTT_SHORTCUT(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	
	local EItemParamType eItemParamType;
	local EShortCutItemType eShortCutType;
	local string ItemName;
	local ShortcutCommandItem commandItem;
	local int shortcutID;
	local string strShort;
	local ItemID MacroID;
	local MacroInfo mInfo;
	local int idx;

	local ShortcutAssignWnd Script;

	Script = ShortcutAssignWnd( GetScript( "OptionWnd.ShortcutTab" ) );

	strShort = "<" $ GetSystemString(1523) $ ": ";
	
	if (eSourceType == NTST_ITEM)
	{   
		if( BoolSelect )
		{
			ParseInt( param, "ItemSubType", Item.ItemSubType);
			ParseString( param, "Name", Item.Name);
			ParseInt( param, "RefineryOp1", Item.RefineryOp1);
			ParseInt( param, "RefineryOp2", Item.RefineryOp2);
			eShortCutType = EShortCutItemType(Item.ItemSubType);
			//아이템 이름 취득
			ItemName = class'UIDATA_ITEM'.static.GetRefineryItemName( Item.Name, Item.RefineryOp1, Item.RefineryOp2 );
			switch (eShortCutType)
			{
			case SCIT_ITEM:
				ReturnTooltip_NTT_ITEM(param, "inventory", eSourceType);
				break;
			case SCIT_SKILL:
				ReturnTooltip_NTT_SKILL(param, eSourceType);
				break;
			case SCIT_ACTION:
			case SCIT_MACRO:
				if (eSourceType == NTST_ITEM)
				{
					ParseString( param, "Name", Item.Name);
					ParseString( param, "Description", Item.Description);
					ParseInt( param, "ClassID", MacroID.ClassID);
					
					class'UIDATA_MACRO'.static.GetMacroInfo( MacroID, mInfo );
					
					m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;

					//액션 이름
					StartItem();
					m_Info.eType = DIT_TEXT;
					m_Info.t_bDrawOneLine = true;
					m_Info.t_strText = Item.Name;
					EndItem();
					
					//액션 설명

					for ( idx = 0; idx < 12; idx++ )
					{
						if ( mInfo.CommandList[idx] != "" )
						{
							StartItem();
							m_Info.eType = DIT_TEXT;
							m_Info.nOffSetY = 6;
							m_Info.t_bDrawOneLine = false;
							m_Info.bLineBreak = true;
							m_Info.t_color.R = 123;
							m_Info.t_color.G = 63;
							m_Info.t_color.B = 178;
							m_Info.t_color.A = 255;
							m_Info.t_strText = mInfo.CommandList[idx];
							EndItem();
						}
					}
					
				}
				else
				{
					return;
				}
					
				ReturnTooltipInfo(m_Tooltip);
				break;
			case SCIT_RECIPE:
			case SCIT_BOOKMARK:
				//아이템 이름
				m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
				StartItem();
				m_Info.eType = DIT_TEXT;
				//m_Info.bLineBreak = true;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_strText = ItemName;
				EndItem();
				break;
			default:
				ReturnTooltipInfo(m_Tooltip);
				break;
			}
			ParseINT(param, "ShortcutID", shortcutID);
			if( GetOptionBool( "Game", "EnterChatting" ) )
			{
				class'ShortcutAPI'.static.GetAssignedKeyFromCommand("TempStateShortcut", "UseShortcutItem Num=" $ shortcutID, commandItem);
			}
			else
			{
				class'ShortcutAPI'.static.GetAssignedKeyFromCommand("GamingStateShortcut", "UseShortcutItem Num=" $ shortcutID, commandItem);
			}
			
			
			//단축키 설명...
			if( commandItem.subkey1 != "" )
			{
				strShort = strShort $ Script.GetUserReadableKeyName( commandItem.subkey1 ) $ "+";
			}
			if( commandItem.subkey2 != "" )
			{
				strShort = strShort $ Script.GetUserReadableKeyName( commandItem.subkey2 ) $ "+";
			}
			if( commandItem.Key != "" )
			{
				strShort = strShort $ Script.GetUserReadableKeyName( commandItem.Key ) $ ">";
			}

			if( commandItem.subkey1 == "" && commandItem.subkey2 == "" && commandItem.Key == "" )
			{
				strShort = strShort $ GetSystemString(27) $">";
			}
			
			//줄추가~
			AddTooltipItemBlank(6);		

			StartItem();
			m_Info.eType = DIT_SPLITLINE;
			m_Info.u_nTextureWidth = TOOLTIP_MINIMUM_WIDTH;			
			m_Info.u_nTextureHeight = 1;
			m_Info.u_strTexture ="L2ui_ch3.tooltip_line";
			EndItem();

			if( ItemName != "" )
			{
				AddTooltipItemBlank(5);
				
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_strText = strShort;
				EndItem();
				
				AddTooltipItemBlank(1);
				
				ReturnTooltipInfo(m_Tooltip);				
			}
			return;
		}
		else
		{
			ParseItemID( param, Item.ID );
			ParseString( param, "Name", Item.Name);
			ParseString( param, "AdditionalName", Item.AdditionalName);
			ParseInt( param, "Level", Item.Level);
			ParseInt( param, "Reserved", Item.Reserved);
			ParseInt( param, "Enchanted", Item.Enchanted);
			ParseInt( param, "ItemType", Item.ItemType);
			ParseInt( param, "ItemSubType", Item.ItemSubType);
			ParseInt( param, "CrystalType", Item.CrystalType);
			ParseInt( param, "ConsumeType", Item.ConsumeType);
			ParseInt( param, "RefineryOp1", Item.RefineryOp1);
			ParseInt( param, "RefineryOp2", Item.RefineryOp2);
			ParseINT64( param, "ItemNum", Item.ItemNum);
			ParseInt( param, "MpConsume", Item.MpConsume);
			//branch
			ParseInt ( param, "IsBRPremium", Item.IsBRPremium);
			//end of branch

			eShortCutType = EShortCutItemType(Item.ItemSubType);
			eItemParamType = EItemParamType(Item.ItemType);

			//아이템 이름 취득
			ItemName = class'UIDATA_ITEM'.static.GetRefineryItemName( Item.Name, Item.RefineryOp1, Item.RefineryOp2 );

			switch (eShortCutType)
			{
			case SCIT_ITEM:
				//branch
				AddPrimeItemSymbol(Item);
				//end of branch
				//인첸트 ex) "+10"
				AddTooltipItemEnchant(Item);

				//아이템 이름
				AddTooltipItemName(ItemName, Item, 1);

				//Grade Mark
				AddTooltipItemGrade(Item);

				//아이템 갯수
				AddTooltipItemCount(Item);
				break;
			case SCIT_SKILL:			
				//아이템 이름
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_strText = ItemName;
				EndItem();

				if (Len(Item.AdditionalName)>0)
				{
					StartItem();
					m_Info.eType = DIT_TEXT;
					m_Info.nOffSetX = 5;
					m_Info.t_bDrawOneLine = true;
					m_Info.t_color.R = 255;
					m_Info.t_color.G = 217;
					m_Info.t_color.B = 105;
					m_Info.t_color.A = 255;
					m_Info.t_strText = Item.AdditionalName;
					EndItem();

					Item.Level = class'UIDATA_SKILL'.static.GetEnchantSkillLevel( Item.ID, Item.Level );
				}

				//ex) " Lv "
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_strText = " ";
				EndItem();

				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_color.R = 163;
				m_Info.t_color.G = 163;
				m_Info.t_color.B = 163;
				m_Info.t_color.A = 255;
				m_Info.t_ID = 88;
				EndItem();

				//스킬 레빌
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_color.R = 176;
				m_Info.t_color.G = 155;
				m_Info.t_color.B = 121;
				m_Info.t_color.A = 255;
				m_Info.t_strText = " " $ Item.Level;
				EndItem();

				//MP소모량
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_strText = " (";
				EndItem();

				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_ID = 91;
				EndItem();

				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_strText = ":" $ Item.MpConsume $ ")";
				EndItem();
				break;

			case SCIT_ACTION:
			case SCIT_MACRO:
			case SCIT_RECIPE:
			case SCIT_BOOKMARK:
				//아이템 이름
				StartItem();
				m_Info.eType = DIT_TEXT;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_strText = ItemName;
				EndItem();
				break;
			}
		}
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// RECIPE_MANUFACTURE
function ReturnTooltip_NTT_RECIPE_MANUFACTURE(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	local bool bLargeWidth;
//	local RecipeManufactureWnd script_rm;
	local string nTmp,strCry,strSell;
//	script_rm = RecipeManufactureWnd(GetScript("RecipeManufactureWnd"));
	if (eSourceType == NTST_ITEM)
	{
		nTmp = "";
		ParseString( param, "Name", Item.Name);
		ParseString( param, "Description", Item.Description);
		ParseString( param, "AdditionalName", Item.AdditionalName);
		ParseINT64( param, "Reserved64", Item.Reserved64);
		ParseInt( param, "CrystalType", Item.CrystalType);
		ParseINT64( param, "ItemNum", Item.ItemNum);
		
		m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
		
		//아이템 이름
		AddTooltipItemName(Item.Name, Item, 1);
		
		//Grade Mark
		AddTooltipItemGrade(Item);
		
		//ex) "필요수 : 2"
		AddTooltipItemOption(736, MakeCostString(Int64ToString(Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeManufactureWnd.QuantityEB"))))), true, true, false);
		
		if ((Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeManufactureWnd.QuantityEB")))) >(Item.ItemNum))
			nTmp = " (" $ MakeCostString(Int64ToString(Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeManufactureWnd.QuantityEB"))) - Item.ItemNum)) $ ")";
		//ex) "보유수 : 0"
		if (Int64ToString(Item.ItemNum)=="")
			AddTooltipItemOption(737, "0" $ nTmp, true, true, false);
		else
			AddTooltipItemOption(737, MakeCostString(Int64ToString(Item.ItemNum)) $ nTmp, true, true, false);	
		
//		if ((Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeManufactureWnd.QuantityEB")))) >
//			(Item.ItemNum))
//				AddTooltipItemOption(0, Int64ToString(Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeManufactureWnd.QuantityEB"))) - Item.ItemNum), false, true, false);
		
		//설명
		ParseString(Item.Description, "Cry", strCry);
		ParseString(Item.Description, "Sell", strSell);
		if (Len(Item.Description)>(Len(strCry) + 5 + Len(strSell) + 5))
		{
			bLargeWidth = true;
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			if (Len(strSell)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strSell) - 5);
			if (Len(strCry)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strCry) - 5);
		//	if (Len(strTmp)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strTmp) - 4);
		//	Item.Description = Left(Item.Description,Len(Item.Description) - Len(strTmp)  - Len(strCry) -  Len(strSell) );
		//	if (Right(Item.Description,1) != ".") Item.Description = Item.Description $ ".";
			m_Info.t_strText = Item.Description;
			EndItem();
		}
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////

function ReturnTooltip_NTT_RECIPE_MANUFACTUREBUY(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	local bool bLargeWidth;
//	local RecipeManufactureWnd script_rm;
	local string nTmp,strCry,strSell;
//	script_rm = RecipeManufactureWnd(GetScript("RecipeManufactureWnd"));
	if (eSourceType == NTST_ITEM)
	{
		nTmp = "";
		ParseString( param, "Name", Item.Name);
		ParseString( param, "Description", Item.Description);
		ParseString( param, "AdditionalName", Item.AdditionalName);
		ParseINT64( param, "Reserved64", Item.Reserved64);
		ParseInt( param, "CrystalType", Item.CrystalType);
		ParseINT64( param, "ItemNum", Item.ItemNum);
		
		m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
		
		//아이템 이름
		AddTooltipItemName(Item.Name, Item, 1);
		
		//Grade Mark
		AddTooltipItemGrade(Item);
		
		//ex) "필요수 : 2"
		AddTooltipItemOption(736, MakeCostString(Int64ToString(Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeBuyManufactureWnd.QuantityEB"))))), true, true, false);
		
		if ((Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeBuyManufactureWnd.QuantityEB")))) >(Item.ItemNum))
			nTmp = " (" $ MakeCostString(Int64ToString(Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeBuyManufactureWnd.QuantityEB"))) - Item.ItemNum)) $ ")";
		//ex) "보유수 : 0"
		if (Int64ToString(Item.ItemNum)=="")
			AddTooltipItemOption(737, "0" $ nTmp, true, true, false);
		else
			AddTooltipItemOption(737, MakeCostString(Int64ToString(Item.ItemNum)) $ nTmp, true, true, false);	
		
//		if ((Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeManufactureWnd.QuantityEB")))) >
//			(Item.ItemNum))
//				AddTooltipItemOption(0, Int64ToString(Item.Reserved64 * IntToInt64(int(class'UIAPI_EDITBOX'.static.GetString("RecipeManufactureWnd.QuantityEB"))) - Item.ItemNum), false, true, false);
		
		//설명
		ParseString(Item.Description, "Cry", strCry);
		ParseString(Item.Description, "Sell", strSell);
		if (Len(Item.Description)>(Len(strCry) + 5 + Len(strSell) + 5))
		{
			bLargeWidth = true;
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.bLineBreak = true;
			m_Info.t_color.R = 178;
			m_Info.t_color.G = 190;
			m_Info.t_color.B = 207;
			m_Info.t_color.A = 255;
			if (Len(strSell)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strSell) -5);
			if (Len(strCry)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strCry) - 5);
		//	if (Len(strTmp)>0) Item.Description = Left(Item.Description, Len(Item.Description) - Len(strTmp) - 4);
		//	Item.Description = Left(Item.Description,Len(Item.Description) - Len(strTmp)  - Len(strCry) -  Len(strSell) );
			m_Info.t_strText = Item.Description;
			EndItem();
		}
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// PLEDGEINFO
function ReturnTooltip_NTT_CLANINFO(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		//ex) "직업 : 엘븐메이지"
		AddTooltipItemOption(391, GetClassType(int(record.LVDataList[2].szData)), true, true, true);
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

//by Merc
function ReturnTooltip_NTT_RADARINFO(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		//ex) "직업 : 엘븐메이지"
		AddTooltipItemOption(391, GetClassType(int(record.LVDataList[3].szData)), true, true, true);
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

// AutoSS
function ReturnTooltip_NTT_AUTOSSINFO(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	local string ItemName;
	
	if (eSourceType == NTST_ITEM)
	{
			ParseItemID( param, Item.ID );
			ParseString( param, "Name", Item.Name);
			ParseString( param, "AdditionalName", Item.AdditionalName);
			ParseInt( param, "Level", Item.Level);
			ParseInt( param, "Reserved", Item.Reserved);
			ParseInt( param, "Enchanted", Item.Enchanted);
			ParseInt( param, "ItemType", Item.ItemType);
			ParseInt( param, "ItemSubType", Item.ItemSubType);
			ParseInt( param, "CrystalType", Item.CrystalType);
			ParseInt( param, "ConsumeType", Item.ConsumeType);
			ParseInt( param, "RefineryOp1", Item.RefineryOp1);
			ParseInt( param, "RefineryOp2", Item.RefineryOp2);
			ParseINT64( param, "ItemNum", Item.ItemNum);
			ParseInt( param, "MpConsume", Item.MpConsume);
			ParseInt ( param, "IsBRPremium", Item.IsBRPremium);
			ItemName = class'UIDATA_ITEM'.static.GetRefineryItemName( Item.Name, Item.RefineryOp1, Item.RefineryOp2 );
			AddPrimeItemSymbol(Item);				
			AddTooltipItemEnchant(Item);
			AddTooltipItemName(ItemName, Item, 1);
			AddTooltipItemGrade(Item);
			AddTooltipItemCount(Item);
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}


// RadarResize
function ReturnTooltip_NTT_RADARRESIZE(string param, ETooltipSourceType eSourceType)
{
	local ItemInfo Item;
	
	if (eSourceType == NTST_ITEM)
	{
			ParseString( param, "Name", Item.Name);
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_strText = Item.Name;
			EndItem();
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

//선준 수정(2010.03.30) 완료
function ReturnTooltip_NTT_POSTINFO(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );

		//ex) "직업 : 엘븐메이지"
		AddTooltipItemOption(391, GetClassType(int(record.LVDataList[1].szData)), true, true, true);
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}


//선준 수정(2010.02.22 ~ 03.08) 완료
/////////////////////////////////////////////////////////////////////////////////
// ROOMLIST
function ReturnTooltip_NTT_ROOMLIST(string param, ETooltipSourceType eSourceType)
{
	local int i;
	local LVDataRecord record;
	local int len;
	
	m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH + 40;

	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		len = int( record.LVDataList[6].szData );

		for( i = 0 ; i < len ; i++ )
		{
			StartItem();
			m_Info.eType = DIT_TEXTURE;


			if (!class'UIAPI_CHECKBOX'.static.IsChecked("FlexOptionWnd.enableDetailIcons"))
			{
				m_Info.nOffSetX = -2;
				m_Info.nOffSetY = -2;	
			}
	
			m_Info.u_nTextureWidth = 16;
			m_Info.u_nTextureHeight = 16;

			m_Info.u_strTexture = GetDetailedClassIconName( record.LVDataList[7 + i].nReserved1, 4);
			EndItem();

			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.t_bDrawOneLine = false;
			m_Info.t_color.R = 163;
			m_Info.t_color.G = 163;
			m_Info.t_color.B = 163;
			m_Info.t_color.A = 255;
			m_Info.t_strText = " " $ record.LVDataList[7 + i].szData;
			EndItem();
			if( i != len - 1 )
			{
				AddTooltipItemBlank(2);
			}
		}

		//ex) "직업 : 엘븐메이지"
		//AddTooltipItemOption(391, GetClassType(int(record.LVDataList[2].szData)), true, true, true);
	}
	else
	{
		return;
	}

	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// USERLIST
function ReturnTooltip_NTT_USERLIST(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH + 80;

	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		//ex) "직업 : 엘븐메이지"
		AddTooltipItemOption(391, GetClassType(int(record.LVDataList[1].szData)), true, true, true);
		
		AddTooltipItemBlank(0);
		//ex)귀속 지역 : 
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_color.R = 163;
		m_Info.t_color.G = 163;
		m_Info.t_color.B = 163;
		m_Info.t_color.A = 255;
		m_Info.t_strText = GetSystemString( 2276 ) $ " : ";
		EndItem();
		
		//설명
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		if( record.LVDataList[5].szData == "" )
		{			
			m_Info.t_strText = GetSystemString( 27 );
		}
		else
		{			
			m_Info.t_strText = record.LVDataList[5].szData;
		}
		EndItem();
		
		
	}
	else
	{
		return;
	}
	
	
	ReturnTooltipInfo(m_Tooltip);
}


/////////////////////////////////////////////////////////////////////////////////
// PARTYMATCH
function ReturnTooltip_NTT_PARTYMATCH(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH + 80;

	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		//ex) "직업 : 엘븐메이지"
		AddTooltipItemOption(391, GetClassType(int(record.LVDataList[1].szData)), true, true, true);
		
		//선준 수정(2010.02.22 ~ 03.08) 완료
		//ex)현재 위치 : 글루디오
		AddTooltipItemOption(471, GetZoneNameWithZoneID(int(record.LVDataList[3].szReserved)), true, true, true);
		
		/*
		AddTooltipItemBlank(0);
		//귀속지역
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = false;
		m_Info.t_color.R = 163;
		m_Info.t_color.G = 163;
		m_Info.t_color.B = 163;
		m_Info.t_color.A = 255;
		m_Info.t_strText = GetSystemString( 2276 ) @ ":";
		EndItem();		
		
		//설명
		
		StartItem();
		m_Info.eType = DIT_TEXT;		
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		if( record.LVDataList[4].szData == "" )
		{
			//m_Info.bLineBreak = true;
			m_Info.t_strText = "" @ GetSystemString( 27 );
		}
		else
		{
			m_Info.bLineBreak = true;
			m_Info.t_strText = record.LVDataList[4].szData;
		}
		EndItem();
		*/

		
		AddTooltipItemBlank(0);
		//ex)귀속 지역 : 
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_color.R = 163;
		m_Info.t_color.G = 163;
		m_Info.t_color.B = 163;
		m_Info.t_color.A = 255;
		m_Info.t_strText = GetSystemString( 2276 ) $ " : ";
		//m_Info.t_strText = "귀속 지역 : ";
		EndItem();
		
		//설명
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		if( record.LVDataList[4].szReserved == "" )
		{			
			m_Info.t_strText = GetSystemString( 27 );
		}
		else
		{			
			m_Info.t_strText = record.LVDataList[4].szReserved;
		}
		EndItem();
		
		
	}
	else
	{
		return;
	}
	
	
	ReturnTooltipInfo(m_Tooltip);
}

//선준 추가 UNION 예전 직업만 나오는 경우.
/////////////////////////////////////////////////////////////////////////////////
// UINONLIST
function ReturnTooltip_NTT_UNIONLIST(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		//ex) "직업 : 엘븐메이지"
		AddTooltipItemOption(391, GetClassType(int(record.LVDataList[1].szData)), true, true, true);
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// QUESTLIST
function ReturnTooltip_NTT_QUESTLIST(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	local int nTmp;
	
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		//퀘스트 이름
		AddTooltipItemOption(1200, record.LVDataList[0].szData, true, true, true);
		
		//반복성
		switch(record.LVDataList[3].nReserved1)
		{
		case 0:
		case 2:
			nTmp = 861;
			break;
		case 1:
		case 3:
			nTmp = 862;
			break;
		}
		AddTooltipItemOption2(1202, nTmp, true, true, false);
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// RAIDLIST
function ReturnTooltip_NTT_RAIDLIST(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		if (Len(record.szReserved)<1)
			return;
		
		m_Tooltip.MinimumWidth = TOOLTIP_MINIMUM_WIDTH;
		
		//레이드 설명
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = false;
		m_Info.t_color.R = 178;
		m_Info.t_color.G = 190;
		m_Info.t_color.B = 207;
		m_Info.t_color.A = 255;
		m_Info.t_strText = record.szReserved;
		EndItem();
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// QUESTINFO
function ReturnTooltip_NTT_QUESTINFO(string param, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	local int nTmp;
	local int Width1;
	local int Width2;
	local int Height;
		
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		//퀘스트 이름
		AddTooltipItemOption(1200, record.LVDataList[0].szData, true, true, true);
		
		//수행조건
		AddTooltipItemOption(1201, record.LVDataList[1].szData, true, true, false);
		
		//Width결정!
		GetTextSizeDefault(GetSystemString(1200) $ " : " $ record.LVDataList[0].szData, Width1, Height);
		GetTextSizeDefault(GetSystemString(1201) $ " : " $ record.LVDataList[1].szData, Width2, Height);
		if (Width2>Width1)
			Width1 = Width2;
		if (TOOLTIP_MINIMUM_WIDTH>Width1)
			Width1 = TOOLTIP_MINIMUM_WIDTH;
		m_Tooltip.MinimumWidth = Width1 + 30;
		
		//추천레벨
		AddTooltipItemOption(922, record.LVDataList[2].szData, true, true, false);
		
		//반복성
		switch(record.LVDataList[3].nReserved1)
		{
		case 0:
		case 2:
			nTmp = 861;
			break;
		case 1:
		case 3:
			nTmp = 862;
			break;
		}
		AddTooltipItemOption2(1202, nTmp, true, true, false);
		
		//퀘스트설명
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.nOffSetY = 6;
		m_Info.t_bDrawOneLine = false;
		m_Info.bLineBreak = true;
		m_Info.t_color.R = 178;
		m_Info.t_color.G = 190;
		m_Info.t_color.B = 207;
		m_Info.t_color.A = 255;
		m_Info.t_strText = record.szReserved;
		EndItem();
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

/////////////////////////////////////////////////////////////////////////////////
// MANOR
function ReturnTooltip_NTT_MANOR(string param, string TooltipType, ETooltipSourceType eSourceType)
{
	local LVDataRecord record;
	
	local int idx1;
	local int idx2;
	local int idx3;
	
	if (eSourceType == NTST_LIST)
	{
		ParamToRecord( param, record );
		
		if (TooltipType == "ManorSeedInfo")
		{
			idx1 = 4;
			idx2 = 5;
			idx3 = 6;
		}
		else if (TooltipType == "ManorCropInfo")
		{
			idx1 = 5;
			idx2 = 6;
			idx3 = 7;
		}
		else if (TooltipType == "ManorSeedSetting")
		{
			idx1 = 7;
			idx2 = 8;
			idx3 = 9;
		}
		else if (TooltipType == "ManorCropSetting")
		{
			idx1 = 9;
			idx2 = 10;
			idx3 = 11;
		}
		else if (TooltipType == "ManorDefaultInfo")
		{
			idx1 = 1;
			idx2 = 4;
			idx3 = 5;
		}
		else if (TooltipType == "ManorCropSell")
		{
			idx1 = 7;
			idx2 = 8;
			idx3 = 9;
		}
		
		// 씨앗 or 작물 이름
		AddTooltipItemOption(0, record.LVDataList[0].szData, false, true, true);
		
		// 레벨
		AddTooltipItemOption(537, record.LVDataList[idx1].szData, true, true, false);

		// 보상 타입1
		AddTooltipItemOption(1134, record.LVDataList[idx2].szData, true, true, false);
		
		// 보상 타입2
		AddTooltipItemOption(1135, record.LVDataList[idx3].szData, true, true, false);
	}
	else
	{
		return;
	}
		
	ReturnTooltipInfo(m_Tooltip);
}

// [퀘스트 아이템 툴팁 추가]
function ReturnTooltip_NTT_QUESTREWARDS(string param, ETooltipSourceType eSourceType)
{
	// [퀘스트 아이템 툴팁 추가] 이 부분에 퀘스트 아이템 툴팁에 걸맞는 코드가 들어가면 될 것 같습니다.
	// 2009.10.14
	// ReturnTooltip_NTT_ITEM(param, "Inventoty", eSourceType);
	ReturnTooltip_NTT_ITEM(param, "QuestReward", eSourceType);
}

//"XXX : YYYY" 형태의 TooltipItem을 편하게 추가해 준다.
function AddTooltipItemOption(int TitleID, string Content, bool bTitle, bool bContent, bool IamFirst)
{
	if (bTitle)
	{
		StartItem();
		m_Info.eType = DIT_TEXT;
		if (!IamFirst)
			m_Info.nOffSetY = 6;
		m_Info.bLineBreak = true;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 163;
		m_Info.t_color.G = 163;
		m_Info.t_color.B = 163;
		m_Info.t_color.A = 255;
		m_Info.t_ID = TitleID;
		EndItem();
	}
	
	if (Content != "0")
	{
		if (bContent)
		{
			if (bTitle)
			{
				StartItem();
				m_Info.eType = DIT_TEXT;
				if (!IamFirst)
					m_Info.nOffSetY = 6;
				m_Info.t_bDrawOneLine = true;
				m_Info.t_color.R = 163;
				m_Info.t_color.G = 163;
				m_Info.t_color.B = 163;
				m_Info.t_color.A = 255;
				m_Info.t_strText = " : ";
				EndItem();
			}
			
			StartItem();
			m_Info.eType = DIT_TEXT;
			if (!IamFirst)
				m_Info.nOffSetY = 6;
			if (!bTitle)
				m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 176;
			m_Info.t_color.G = 155;
			m_Info.t_color.B = 121;
			m_Info.t_color.A = 255;
			m_Info.t_strText = Content;
			EndItem();
		}
	}
}

//"XXX : YYYY" 형태의 TooltipItem을 편하게 추가해 준다.
//SYSSTRING : SYSSTRING
function AddTooltipItemOption2(int TitleID, int ContentID, bool bTitle, bool bContent, bool IamFirst)
{
	if (bTitle)
	{
		StartItem();
		m_Info.eType = DIT_TEXT;
		if (!IamFirst)
			m_Info.nOffSetY = 6;
		m_Info.bLineBreak = true;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 163;
		m_Info.t_color.G = 163;
		m_Info.t_color.B = 163;
		m_Info.t_color.A = 255;
		m_Info.t_ID = TitleID;
		EndItem();
	}
	
	if (bContent)
	{	
		if (bTitle)
		{
			StartItem();
			m_Info.eType = DIT_TEXT;
			if (!IamFirst)
				m_Info.nOffSetY = 6;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_color.R = 163;
			m_Info.t_color.G = 163;
			m_Info.t_color.B = 163;
			m_Info.t_color.A = 255;
			m_Info.t_strText = " : ";
			EndItem();
		}		
		
		StartItem();
		m_Info.eType = DIT_TEXT;
		if (!IamFirst)
			m_Info.nOffSetY = 6;
		if (!bTitle)
			m_Info.bLineBreak = true;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		m_Info.t_ID = ContentID;
		EndItem();
	}
}

//아이템의 색상을 다시 설정해준다.
function SetTooltipItemColor(int R, int G, int B, int Offset)
{
	local int idx;
	idx = m_Tooltip.DrawList.Length-1-Offset;
	m_Tooltip.DrawList[idx].t_color.R = R;
	m_Tooltip.DrawList[idx].t_color.G = G;
	m_Tooltip.DrawList[idx].t_color.B = B;
	m_Tooltip.DrawList[idx].t_color.A = 255;
}

//빈공간의 TooltipItem을 추가한다.
function AddTooltipItemBlank(int Height)
{
	StartItem();
	m_Info.eType = DIT_BLANK;
	m_Info.b_nHeight = Height;
	EndItem();
}


function AddTooltipSimpleName( string strText)
{
	StartItem();
	m_Info.eType = DIT_TEXT;
	m_Info.t_bDrawOneLine = true;
	m_Info.t_strText = strText;
	EndItem();	
}


//인첸트
function AddTooltipItemEnchant(ItemInfo Item)
{
	local EItemParamType eItemParamType;
	
	eItemParamType = EItemParamType(Item.ItemType);
	if (Item.Enchanted>0 && IsEnchantableItem(eItemParamType))
	{
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 176;
		m_Info.t_color.G = 155;
		m_Info.t_color.B = 121;
		m_Info.t_color.A = 255;
		m_Info.t_strText = "+" $ Item.Enchanted $ " ";
		EndItem();
	}	
}

//아이템 이름 + AdditionalName
function AddTooltipItemName(string Name, ItemInfo Item, int AddTooltipItemName)
{
	StartItem();
	m_Info.eType = DIT_TEXT;
	m_Info.t_bDrawOneLine = true;
	switch (AddTooltipItemName)
	{
		case 0:
		m_Info.t_color.R = 137;
		m_Info.t_color.G = 137;
		m_Info.t_color.B = 137;
		m_Info.t_color.A = 255;
		break;
		case 1:
		m_Info.t_color.R = 255;
		m_Info.t_color.G = 255;
		m_Info.t_color.B = 255;
		m_Info.t_color.A = 255;
		break;
		case 2:
		m_Info.t_color.R = 255;
		m_Info.t_color.G = 251;
		m_Info.t_color.B = 4;
		m_Info.t_color.A = 255;
		break;
		case 3:
		m_Info.t_color.R = 255;
		m_Info.t_color.G = 0;
		m_Info.t_color.B = 255;
		m_Info.t_color.A = 255;
		break;
	}
	m_Info.t_strText = Name;
	EndItem();
	
	//Additional Name
	if (Len(Item.AdditionalName)>0)
	{
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_color.R = 255;
		m_Info.t_color.G = 217;
		m_Info.t_color.B = 105;
		m_Info.t_color.A = 255;
		m_Info.t_strText = " " $ Item.AdditionalName;
		EndItem();
	}
}

//Grade Mark
function AddTooltipItemGrade(ItemInfo Item)
{
	local string TextureName;
	
	TextureName = GetItemGradeTextureName(Item.CrystalType);
	//debug ("TextureName" @ TextureName);
	//debug (TextureName @  Item.CrystalType @ GetItemGradeTextureName(Item.CrystalType));
	if (Len(TextureName)>0)
	{
		StartItem();
		m_Info.eType = DIT_TEXTURE;
		m_Info.nOffSetX = 2;
		m_Info.nOffSetY = 0;
		
		//S80 그레이드일 경우에 한해 아이콘 텍스쳐 크기를 2배로 늘린다.
		//debug ("텍스쳐 이름"@ Item.CrystalType );
		if (Item.CrystalType == 6 || Item.CrystalType == 7)
		{
			m_Info.u_nTextureWidth = 32;
			m_Info.u_nTextureHeight = 16;
			
			m_Info.u_nTextureUWidth = 32;
			m_Info.u_nTextureUHeight = 16;
			
			//debug (TextureName @  Item.CrystalType @ GetItemGradeTextureName(Item.CrystalType));
		}
		//기타 그레이드에 대해 아이콘 텍스쳐 크기를 세팅한다. 
		else
		{
			m_Info.u_nTextureWidth = 16;
			m_Info.u_nTextureHeight = 16;
			
			m_Info.u_nTextureUWidth = 16;
			m_Info.u_nTextureUHeight = 16;
		}
		m_Info.u_strTexture = TextureName;
		EndItem();
	}
}

//Stackable Count
function AddTooltipItemCount(ItemInfo Item)
{
	if (IsStackableItem(Item.ConsumeType))
	{
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_strText = " (" $ MakeCostString(Int64ToString(Item.ItemNum)) $ ")";
		EndItem();
	}	
}

//제련 색상
function GetRefineryColor(int Quality, out int R, out int G, out int B)
{
	switch (Quality)
	{
	case 1:
		R = 187;
		G = 181;
		B = 138;
	break;
	case 2:
		R = 132;
		G = 174;
		B = 216;
	break;
	case 3:
		R = 193;
		G = 112;
		B = 202;
	break;
	case 4:
		R = 225;
		G = 109;
		B = 109;
	break;
	default:
		R = 187;
		G = 181;
		B = 138;
	break;
	}
}

//속성 게이지 그려주기
function AddTooltipItemAttributeGage(ItemInfo Item)
{
	local int i;
	//local int highAttrValue[6];
	local Array<string> textureName, tooltipStr;
	
	for(i = 0; i < 6; i++)
	{
		textureName[i] = "";
		tooltipStr[i] = "";
	}
	
	NowAttrLv =0;
	NowMaxValue =0;
	NowValue =0;

	
	// 공격 아이템 속성
	if (Item.AttackAttributeValue  > 0)
	{
		
		SetAttackAttribute(Item.AttackAttributeValue,ATTRIBUTE_FIRE);
		SetAttackAttribute(Item.AttackAttributeValue,ATTRIBUTE_WATER);
		SetAttackAttribute(Item.AttackAttributeValue,ATTRIBUTE_WIND);
		SetAttackAttribute(Item.AttackAttributeValue,ATTRIBUTE_EARTH);
		SetAttackAttribute(Item.AttackAttributeValue,ATTRIBUTE_HOLY);
		SetAttackAttribute(Item.AttackAttributeValue,ATTRIBUTE_UNHOLY); //레벨과 현제값등을 구한다.
		
		switch(Item.AttackAttributeType)
		{
			case ATTRIBUTE_FIRE:
				textureName[ATTRIBUTE_FIRE] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_FIRE";
				tooltipStr[ATTRIBUTE_FIRE] =GetSystemString(1622) $ " Lv " $ String(AttackAttLevel[ATTRIBUTE_FIRE]) $ " ("$ GetSystemString(1622) $ " " $ GetSystemString(55) $ " " $ String(Item.AttackAttributeValue) $")";
				break;
			case ATTRIBUTE_WATER:
				textureName[ATTRIBUTE_WATER] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_WATER";
				tooltipStr[ATTRIBUTE_WATER] =GetSystemString(1623) $ " Lv " $ String(AttackAttLevel[ATTRIBUTE_WATER]) $ " ("$ GetSystemString(1623) $ " " $ GetSystemString(55) $ " " $String(Item.AttackAttributeValue) $ ")";
				break;
			case ATTRIBUTE_WIND:
				textureName[ATTRIBUTE_WIND] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_WIND";
				tooltipStr[ATTRIBUTE_WIND] =GetSystemString(1624) $ " Lv " $ String(AttackAttLevel[ATTRIBUTE_WIND]) $ " ("$ GetSystemString(1624) $ " " $ GetSystemString(55) $ " " $String(Item.AttackAttributeValue) $ ")";
				break;
			case ATTRIBUTE_EARTH:
				textureName[ATTRIBUTE_EARTH] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_EARTH";
				tooltipStr[ATTRIBUTE_EARTH] =GetSystemString(1625) $ " Lv " $ String(AttackAttLevel[ATTRIBUTE_EARTH]) $ " ("$ GetSystemString(1625) $ " " $ GetSystemString(55) $ " " $ String(Item.AttackAttributeValue) $")";
				break;
			case ATTRIBUTE_HOLY:
				textureName[ATTRIBUTE_HOLY] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_DIVINE";
				tooltipStr[ATTRIBUTE_HOLY] =GetSystemString(1626) $ " Lv " $ String(AttackAttLevel[ATTRIBUTE_HOLY]) $ " ("$ GetSystemString(1626) $ " " $ GetSystemString(55) $ " " $String(Item.AttackAttributeValue) $ ")";
				break;
			case ATTRIBUTE_UNHOLY:
				textureName[ATTRIBUTE_UNHOLY] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_DARK";
				tooltipStr[ATTRIBUTE_UNHOLY] =GetSystemString(1627) $ " Lv " $ String(AttackAttLevel[ATTRIBUTE_UNHOLY]) $ " ("$ GetSystemString(1627) $ " " $ GetSystemString(55) $ " " $String(Item.AttackAttributeValue) $ ")";
				break;
		}
	}
	else	// 방어 아이템 속성
	{	
		SetDefAttribute(Item.DefenseAttributeValueFire,ATTRIBUTE_FIRE);
		SetDefAttribute(Item.DefenseAttributeValueWater,ATTRIBUTE_WATER);
		SetDefAttribute(Item.DefenseAttributeValueWind,ATTRIBUTE_WIND);
		SetDefAttribute(Item.DefenseAttributeValueEarth,ATTRIBUTE_EARTH);
		SetDefAttribute(Item.DefenseAttributeValueHoly,ATTRIBUTE_HOLY);
		SetDefAttribute(Item.DefenseAttributeValueUnholy,ATTRIBUTE_UNHOLY); //레벨과 현제값등을 구한다.

		if(Item.DefenseAttributeValueFire != 0) //파이어 속성 툴팁 그리기
		{
			textureName[ATTRIBUTE_FIRE] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_FIRE";
			tooltipStr[ATTRIBUTE_FIRE] =GetSystemString(1623) $ " Lv " $ String(DefAttLevel[ATTRIBUTE_FIRE]) $ " ("$ GetSystemString(1622) $ " " $ GetSystemString(54) $ " " $ String(Item.DefenseAttributeValueFire) $")";
		}
		if(Item.DefenseAttributeValueWater != 0) //물 속성 툴팁 그리기
		{
			textureName[ATTRIBUTE_WATER] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_WATER";
			tooltipStr[ATTRIBUTE_WATER] =GetSystemString(1622) $ " Lv " $ String(DefAttLevel[ATTRIBUTE_WATER]) $ " ("$ GetSystemString(1623) $ " " $ GetSystemString(54) $ " " $String(Item.DefenseAttributeValueWater) $ ")";
		}
		if(Item.DefenseAttributeValueWind != 0) //바람 속성 툴팁 그리기
		{
			textureName[ATTRIBUTE_WIND] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_WIND";
			tooltipStr[ATTRIBUTE_WIND] =GetSystemString(1625) $ " Lv " $ String(DefAttLevel[ATTRIBUTE_WIND]) $ " ("$ GetSystemString(1624) $ " " $ GetSystemString(54) $ " " $String(Item.DefenseAttributeValueWind) $")";
		}
		if(Item.DefenseAttributeValueEarth != 0) //땅 속성 툴팁 그리기
		{
			textureName[ATTRIBUTE_EARTH] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_EARTH";
			tooltipStr[ATTRIBUTE_EARTH] =GetSystemString(1624) $ " Lv " $ String(DefAttLevel[ATTRIBUTE_EARTH]) $ " ("$ GetSystemString(1625) $ " " $ GetSystemString(54) $ " " $String(Item.DefenseAttributeValueEarth) $ ")";
		}
		if(Item.DefenseAttributeValueHoly != 0) //신성 속성 툴팁 그리기
		{
			textureName[ATTRIBUTE_HOLY] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_DIVINE";
			tooltipStr[ATTRIBUTE_HOLY] =GetSystemString(1627) $ " Lv " $ String(DefAttLevel[ATTRIBUTE_HOLY]) $ " ("$ GetSystemString(1626) $ " " $ GetSystemString(54) $ " " $ String(Item.DefenseAttributeValueHoly) $")";
		}
		if(Item.DefenseAttributeValueUnholy != 0) //암흑 속성 툴팁 그리기
		{
			textureName[ATTRIBUTE_UNHOLY] = "L2UI_CT1.Gauges.Gauge_DF_Attribute_DARK";
			tooltipStr[ATTRIBUTE_UNHOLY] =GetSystemString(1626) $ " Lv " $ String(DefAttLevel[ATTRIBUTE_UNHOLY]) $ " ("$ GetSystemString(1627) $ " " $ GetSystemString(54) $ " " $String(Item.DefenseAttributeValueUnholy) $ ")";
		}
		// 방어 아이템 속성
		

	}

	if (Item.AttackAttributeValue  > 0)//공격속성일경우
	{
		for(i = 0; i < 6; i++)
		{
			if(tooltipStr[i] == "") continue;			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 10;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_strText = tooltipStr[i];
			EndItem();
			
			//텍스쳐 두장을 겹쳐 그려야 한다. 
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.nOffSetX = 0;
			m_Info.nOffSetY = 2;
			m_Info.u_nTextureWidth = 140;
			m_Info.u_nTextureHeight = 7;
			m_Info.u_strTexture = textureName[i] $ "_BG";
			EndItem();
			
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.nOffSetX = 0;
			m_Info.nOffSetY = -7;
			m_Info.u_nTextureWidth = AttackAttCurrValue[i] * 140 / AttackAttMaxValue[i] ;
			if( m_Info.u_nTextureWidth > 140) m_Info.u_nTextureWidth = 140;	//넘어가면 걍 140이라는.. ㅋ
			m_Info.u_nTextureHeight = 7;
			m_Info.u_strTexture = textureName[i];
			EndItem();
		}
	
	}
	else{ //방어 속성일 경우
		for(i = 0; i < 6; i++)
		{
			if(tooltipStr[i] == "") continue;			
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 10;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_strText = tooltipStr[i];
			EndItem();
			
			//텍스쳐 두장을 겹쳐 그려야 한다. 
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.nOffSetX = 0;
			m_Info.nOffSetY = 2;
			m_Info.u_nTextureWidth = 140;
			m_Info.u_nTextureHeight = 7;
			m_Info.u_strTexture = textureName[i] $ "_BG";
			EndItem();
			
			StartItem();
			m_Info.eType = DIT_TEXTURE;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.nOffSetX = 0;
			m_Info.nOffSetY = -7;
			
			m_Info.u_nTextureWidth = DefAttCurrValue[i] * 140 / DefAttMaxValue[i] ;
			if( m_Info.u_nTextureWidth > 140) m_Info.u_nTextureWidth = 140;	//넘어가면 걍 140이라는.. ㅋ
			m_Info.u_nTextureHeight = 7;
			m_Info.u_strTexture = textureName[i];
			EndItem();
		}
	}
	
}

// function StartItem()
// {
// 	local DrawItemInfo infoClear;
// 	m_Info = infoClear;
// }
// 
// function EndItem()
// {
// 	m_Tooltip.DrawList.Length = m_Tooltip.DrawList.Length + 1;
// 	m_Tooltip.DrawList[m_Tooltip.DrawList.Length-1] = m_Info;
// }

//퀘스트 아이템의 퀘스트 이름 표시
function AddTooltipItemQuestList(ItemInfo Item)
{
	local int i;
	
	//<관련 퀘스트>
	if(Item.RelatedQuestID[0] != 0)
	{
		StartItem();
		m_Info.eType = DIT_TEXT;
		m_Info.nOffSetY = 10;
		m_Info.bLineBreak = true;
		m_Info.t_bDrawOneLine = true;
		m_Info.t_strText = GetSystemString(1721);
		EndItem();
	}
	
	for(i = 0 ; i<MAX_RELATED_QUEST ; i++)
	{		
		if(Item.RelatedQuestID[i] != 0)
		{
			StartItem();
			m_Info.eType = DIT_TEXT;
			m_Info.nOffSetY = 6;
			m_Info.t_color.R = 163;
			m_Info.t_color.G = 163;
			m_Info.t_color.B = 163;
			m_Info.t_color.A = 255;
			m_Info.bLineBreak = true;
			m_Info.t_bDrawOneLine = true;
			m_Info.t_strText = class'UIDATA_QUEST'.static.GetQuestName(Item.RelatedQuestID[i]);;
			EndItem();
		}
	}
}


// 속성의 레벨값을 전역변수에 저장	//자료가 많아서 전역변수에 집어넣는다. 

function SetAttackAttribute(int Attvalue, int type)
{
	if( AttValue >= 375)	// 9렙	375 ~ 450
	{
		AttackAttLevel[type] = 9;
		AttackAttMaxValue[type] = 75;
		AttackAttCurrValue[type] = AttValue - 375;
	}
	else if( AttValue >= 325)	// 8렙	325 ~ 375
	{
		AttackAttLevel[type] = 8;
		AttackAttMaxValue[type] = 50;
		AttackAttCurrValue[type] = AttValue - 325;
	}
	else if( AttValue >= 300)	// 7렙	300 ~ 325
	{
		AttackAttLevel[type] = 7;
		AttackAttMaxValue[type] = 25;
		AttackAttCurrValue[type] = AttValue - 300;
	}
	else if( AttValue >= 225)	// 6렙	225 ~ 300
	{
		AttackAttLevel[type] = 6;
		AttackAttMaxValue[type] = 75;
		AttackAttCurrValue[type] = AttValue - 225;
	}
	else if( AttValue >= 175)	// 5렙	175 ~ 225
	{
		AttackAttLevel[type] = 5;
		AttackAttMaxValue[type] = 50;
		AttackAttCurrValue[type] = AttValue - 175;
	}
	else if( AttValue >= 150)	// 4렙	150 ~ 175
	{
		AttackAttLevel[type] = 4;
		AttackAttMaxValue[type] = 25;
		AttackAttCurrValue[type] = AttValue - 150;
	}
	else if( AttValue >= 75)	// 3렙	75 ~ 150
	{
		AttackAttLevel[type] = 3;
		AttackAttMaxValue[type] = 75;
		AttackAttCurrValue[type] = AttValue - 75;
	}
	else if( AttValue >= 25)	// 2렙	25~ 75
	{
		AttackAttLevel[type] = 2;
		AttackAttMaxValue[type] = 50;
		AttackAttCurrValue[type] = AttValue - 25;
	}
	else	// else 0~ 25
	{
		AttackAttLevel[type] = 1;
		AttackAttMaxValue[type] = 25;
		AttackAttCurrValue[type] = AttValue;
	}	
}
// 속성의 레벨값을 전역변수에 저장	//자료가 많아서 전역변수에 집어넣는다. 


function SetDefAttribute(int Defvalue, int type)
{
	if( DefValue >= 150)	// 9렙		150~180
	{
		DefAttLevel[type] = 9;
		DefAttMaxValue[type] = 30;
		DefAttCurrValue[type] = DefValue - 150;
	}
	else if( DefValue >= 132)	// 8렙	132 ~ 150
	{
		DefAttLevel[type] = 8;
		DefAttMaxValue[type] = 18;
		DefAttCurrValue[type] = DefValue - 132;
	}
	else if( DefValue >= 120)	// 7렙	120 ~ 132
	{
		DefAttLevel[type] = 7;
		DefAttMaxValue[type] = 12;
		DefAttCurrValue[type] = DefValue - 120;
	}
	else if( DefValue >= 90)	// 6렙	90 ~ 120
	{
		DefAttLevel[type] = 6;
		DefAttMaxValue[type] = 30;
		DefAttCurrValue[type] = DefValue - 90;
	}
	else if( DefValue >= 72)	// 5렙	72 ~ 90
	{
		DefAttLevel[type] = 5;
		DefAttMaxValue[type] = 18;
		DefAttCurrValue[type] = DefValue - 72;
	}
	else if( DefValue >= 60)	// 4렙	60 ~ 72
	{
		DefAttLevel[type] = 4;
		DefAttMaxValue[type] = 12;
		DefAttCurrValue[type] = DefValue - 60;
	}
	else if( DefValue >= 30)	// 3렙	30 ~ 60
	{
		DefAttLevel[type] = 3;
		DefAttMaxValue[type] = 30;
		DefAttCurrValue[type] = DefValue - 30;
	}
	else if( DefValue >= 12)	// 2렙	// 12 ~ 30
	{
		DefAttLevel[type] = 2;
		DefAttMaxValue[type] = 18;
		DefAttCurrValue[type] = DefValue - 12;
	}
	else	// else				// 0~ 12
	{
		DefAttLevel[type] = 1;
		DefAttMaxValue[type] = 12;
		DefAttCurrValue[type] = DefValue;
	}	
}

//branch
function AddPrimeItemSymbol(ItemInfo Item)
{
	local string TextureName;

	if (Item.IsBRPremium != 2)
		return;

	TextureName = GetPrimeItemSymbolName();
	if (Len(TextureName)>0)
	{
		StartItem();
		m_Info.eType = DIT_TEXTURE;
		m_Info.nOffSetX = 2;
		m_Info.nOffSetY = 0;
		
		m_Info.u_nTextureWidth = 16;
		m_Info.u_nTextureHeight = 16;
			
		m_Info.u_nTextureUWidth = 16;
		m_Info.u_nTextureUHeight = 16;

		m_Info.u_strTexture = TextureName;
		EndItem();
	}
}

function int CheckDurability(int itemCheckID)
{
	switch (itemCheckID)
	{
		case 10518:
			return 30;
		break;
		//blue
		case 9932:
		case 14813:
		//red
		case 9918:
		case 9931:
			return 15;
		break;
		case 10519:
			return 10;
		break;
		//blue
		case 9914:
		case 9915:
		case 9916:
		case 9919:
		case 9921:
		case 9926:
		case 9927:
		case 9929:
		case 9930:
		case 10416:
		case 10417:
		case 10424:
		case 10533:
		case 10534:
		case 14810:
		case 14811:
		// red
		case 9917:
		case 9928:
		case 12815:
		case 12816:
		case 14604:
		case 14605:
		case 14812:
		// white
		case 10418:
		case 10419:
		case 10420:
		case 10421:
		case 10422:
		case 14814:
		// orange
		case 9954:
		//black
		case 9922:
		case 9923:
		case 9955:
		case 9956:
		case 9957:
		case 9958:
		case 9959:
			return 6;
		break;
		//blue
		case 9920:
		case 10142:
		case 10141:
			return 3;
		break;
		//blue
		case 10423:
			return 1;
		break;
		default:
			return 5;
		break;	
	}
}
//end of branch

defaultproperties
{
}
