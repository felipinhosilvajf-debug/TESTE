class SkillTrainClanTreeWnd extends UICommonAPI;
	
//////////////////////////////////////////////////////////////////////////////
// CONSTc
//////////////////////////////////////////////////////////////////////////////

const OFFSET_X_ICON_TEXTURE=0;
const OFFSET_Y_ICON_TEXTURE=4;
const OFFSET_Y_SECONDLINE = -14;

const OFFSET_Y_MPCONSUME=3;
const OFFSET_Y_CASTRANGE=0;
const OFFSET_Y_SP=120;

const DISABLE_ALPHA=100;

var int m_iType;
var int m_iID;
var int m_iLevel;

var int ClanClickedID;	// 현재 클랜 스킬 트리 부분이 클릭되어 있는지 확인

var WindowHandle Me;
var TextureHandle texIcon;
var TextBoxHandle txtSkillDesc;
var TextBoxHandle txtName;
var TextBoxHandle txtLvString;
var TextBoxHandle txtLevel;
var TextBoxHandle txtOperateType;
var TextBoxHandle txtMPString;
var TextBoxHandle txtColone1;
var TextBoxHandle txtMP;
var TextBoxHandle txtCastRangeString;
var TextBoxHandle txtColoneCastRange;
var TextBoxHandle txtCastRange;
var TextBoxHandle txtNeedSPString;
var TextBoxHandle txtColone3;
var TextBoxHandle txtNeedSP;
var TextBoxHandle txtNeedItemName;
var TextBoxHandle txtSPString;
var TextBoxHandle txtSP;
var ButtonHandle btnLearn;
var ButtonHandle btnGoBackList;
var TextBoxHandle txtCondition;
var TextBoxHandle txtSelectTree;
var TextBoxHandle txtDescription;
var TextureHandle nowSpBg;
var TextureHandle texNeedItemIcon;
var TextureHandle requestBg;
var TextureHandle texSelectTree;
var TextureHandle texOutlineDown;
var TextureHandle texOutlineTopUp;


//Handle
var ButtonHandle Clan_OrgIcon[CLAN_KNIGHTHOOD_COUNT];
var TextureHandle Clan_OrgHighLight[CLAN_KNIGHTHOOD_COUNT];

function OnRegisterEvent()
{
	RegisterEvent( EV_SkillTrainInfoWndShow );
	RegisterEvent( EV_SkillTrainInfoWndHide );
	RegisterEvent( EV_SkillTrainInfoWndAddExtendInfo );
	RegisterEvent( EV_GamingStateExit );
}

function OnLoad()
{
	if(CREATE_ON_DEMAND==0)
		OnRegisterEvent();

	if(CREATE_ON_DEMAND==0)
		InitHandle();
	else
		InitHandleCOD();

	Load();	
	Initialize();
}

function Initialize()
{
	ClanClickedID = -1;	
	m_iType = -1;
	
	btnLearn.DisableWindow();
}

function DisableAllClanTreeWnd()
{
	local int i;
	
	for( i=0 ; i < CLAN_KNIGHTHOOD_COUNT ; ++i )
	{	
		Clan_OrgIcon[i].SetAlpha(DISABLE_ALPHA);
		Clan_OrgIcon[i].DisableWindow();
	}
}

function InitHandle()
{
	local int i;
	
	Me = GetHandle( "SkillTrainClanTreeWnd" );
	texIcon = TextureHandle ( GetHandle( "SkillTrainClanTreeWnd.texIcon" ) );
	txtSkillDesc = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtSkillDesc" ) );
	txtName = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtName" ) );
	txtLvString = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtLvString" ) );
	txtLevel = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtLevel" ) );
	txtOperateType = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtOperateType" ) );
	txtMPString = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtMPString" ) );
	txtColone1 = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtColone1" ) );
	txtMP = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtMP" ) );
	txtCastRangeString = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtCastRangeString" ) );
	txtColoneCastRange = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtColoneCastRange" ) );
	txtCastRange = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtCastRange" ) );
	txtNeedSPString = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtNeedSPString" ) );
	txtColone3 = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtColone3" ) );
	txtNeedSP = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtNeedSP" ) );
	txtNeedItemName = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtNeedItemName" ) );
	txtSPString = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtSPString" ) );
	txtSP = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtSP" ) );
	btnLearn = ButtonHandle ( GetHandle( "SkillTrainClanTreeWnd.btnLearn" ) );
	btnGoBackList = ButtonHandle ( GetHandle( "SkillTrainClanTreeWnd.btnGoBackList" ) );
	txtCondition = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtCondition" ) );
	txtSelectTree = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtSelectTree" ) );
	txtDescription = TextBoxHandle ( GetHandle( "SkillTrainClanTreeWnd.txtDescription" ) );
	nowSpBg = TextureHandle ( GetHandle( "SkillTrainClanTreeWnd.nowSpBg" ) );
	texNeedItemIcon = TextureHandle ( GetHandle( "SkillTrainClanTreeWnd.texNeedItemIcon" ) );
	requestBg = TextureHandle ( GetHandle( "SkillTrainClanTreeWnd.requestBg" ) );
	texSelectTree = TextureHandle ( GetHandle( "SkillTrainClanTreeWnd.texSelectTree" ) );
	texOutlineDown = TextureHandle ( GetHandle( "SkillTrainClanTreeWnd.texOutlineDown" ) );
	texOutlineTopUp = TextureHandle ( GetHandle( "SkillTrainClanTreeWnd.texOutlineTopUp" ) );
		
	for( i=0 ; i < CLAN_KNIGHTHOOD_COUNT ; ++i )
	{
		Clan_OrgIcon[i] = ButtonHandle(GetHandle("SkillTrainClanTreeWnd.ClanOrgIconWnd" $ (i + 1) $ ".texClanIcon" $ (i + 1)) );
		Clan_OrgHighLight[i] = TextureHandle( GetHandle("SkillTrainClanTreeWnd.ClanOrgIconWnd" $ (i + 1) $ ".texIconHighlight"));
	}
}

function InitHandleCOD()
{
	local int i;

	Me = GetWindowHandle( "SkillTrainClanTreeWnd" );
	texIcon = GetTextureHandle( "SkillTrainClanTreeWnd.texIcon" );
	txtSkillDesc = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtSkillDesc" );
	txtName = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtName" );
	txtLvString = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtLvString" );
	txtLevel = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtLevel" );
	txtOperateType = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtOperateType" );
	txtMPString = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtMPString" );
	txtColone1 = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtColone1" );
	txtMP = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtMP" );
	txtCastRangeString = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtCastRangeString" );
	txtColoneCastRange = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtColoneCastRange" );
	txtCastRange = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtCastRange" );
	txtNeedSPString = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtNeedSPString" );
	txtColone3 = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtColone3" );
	txtNeedSP = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtNeedSP" );
	txtNeedItemName = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtNeedItemName" );
	txtSPString = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtSPString" );
	txtSP = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtSP" );
	btnLearn = GetButtonHandle( "SkillTrainClanTreeWnd.btnLearn" );
	btnGoBackList = GetButtonHandle( "SkillTrainClanTreeWnd.btnGoBackList" );
	txtCondition = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtCondition" );
	txtSelectTree = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtSelectTree" );
	txtDescription = GetTextBoxHandle( "SkillTrainClanTreeWnd.txtDescription" );
	nowSpBg = GetTextureHandle( "SkillTrainClanTreeWnd.nowSpBg" );
	texNeedItemIcon = GetTextureHandle( "SkillTrainClanTreeWnd.texNeedItemIcon" );
	requestBg = GetTextureHandle( "SkillTrainClanTreeWnd.requestBg" );
	texSelectTree = GetTextureHandle( "SkillTrainClanTreeWnd.texSelectTree" );
	texOutlineDown = GetTextureHandle( "SkillTrainClanTreeWnd.texOutlineDown" );
	texOutlineTopUp = GetTextureHandle( "SkillTrainClanTreeWnd.texOutlineTopUp" );
		
	for( i=0 ; i < CLAN_KNIGHTHOOD_COUNT ; ++i )
	{
		Clan_OrgIcon[i] = GetButtonHandle("SkillTrainClanTreeWnd.ClanOrgIconWnd" $ (i + 1) $ ".texClanIcon" $ (i + 1));
		Clan_OrgHighLight[i] = GetTextureHandle("SkillTrainClanTreeWnd.ClanOrgIconWnd" $ (i + 1) $ ".texIconHighlight");
	}
}

function Load()
{
	local int i;

	for( i=0 ; i < CLAN_KNIGHTHOOD_COUNT ; ++i )
	{
		Clan_OrgHighLight[i].HideWindow();	
		Clan_OrgIcon[i].SetAlpha(DISABLE_ALPHA);
		Clan_OrgIcon[i].HideWindow();
	}
}

function OnClickButton( string Name )
{
	local int temp1;
	
	switch( Name )
	{
	case "btnLearn":
		OnbtnLearnClick();
		if(ClanClickedID != -1)	Clan_OrgHighLight[ClanClickedID-1].HideWindow();
		ClanClickedID = -1;
		btnLearn.DisableWindow();
		break;
	case "btnGoBackList":
		HideWindow("SkillTrainClanTreeWnd");
		ShowWindowWithFocus("SkillTrainListWnd");
		if(ClanClickedID != -1)	Clan_OrgHighLight[ClanClickedID-1].HideWindow();
		ClanClickedID = -1;
		btnLearn.DisableWindow();
		break;
	}
	
	if( inStr(Name, "texClanIcon") >-1)
	{
		temp1 = Int( Right (Name , 1));
		if(temp1 == 8) 
		{
			return;	// 아카데미는 무시한다. 
		}
		if(temp1 > 0  && temp1 < CLAN_KNIGHTHOOD_COUNT + 2)
		{
			if( (ClanClickedID < 0) || (temp1 != ClanClickedID)  )	// 클릭된게 없거나, 방금 누른거랑 아까 누른게 다를 때
			{
				Clan_OrgHighLight[temp1-1].ShowWindow();
				if(ClanClickedID != -1)	Clan_OrgHighLight[ClanClickedID-1].HideWindow();	// 다른것을 눌렀을경우 아까 누른 하이라이트 해제
				//nClanIdx = GetClanTypeFromIndex( temp1 - 1);
				//class'UIDATA_CLAN'.static.RequestSubClanSkillList(nClanIdx);
				ClanClickedID = temp1;
				btnLearn.EnableWindow();
			}		
			else if(temp1 == ClanClickedID)	// 방금 누른것을 또 눌렀을때 체크 해제
			{
				Clan_OrgHighLight[temp1-1].HideWindow();
				//class'UIDATA_CLAN'.static.RequestClanSkillList();
				ClanClickedID = -1;
				btnLearn.DisableWindow();
			}
		}
	}
}

function OnbtnLearnClick()
{
	local int nClanIdx;
	
	switch(m_iType)
	{
	case ESTT_SUB_CLAN:		// 4번째 인자가 없을경우 시스템 메세지를 띄워준다. 
		nClanIdx = GetClanTypeFromIndex( ClanClickedID - 1);
		RequestAcquireSkillSubClan(m_iID, m_iLevel, m_iType, nClanIdx);			// 여기 4번째 인자로 하위 혈맹의 인덱스를 넣을 것. -lpislhy
		HideWindow("SkillTrainClanTreeWnd");
		break;
	}
}

function OnEvent(int Event_ID, string param)
{
	local int iType;

	local string strIconName;
	local string strName;
	local int iID;
	local int iLevel;
	local int iSPConsume;
	local INT64 iEXPConsume;

	local string strDescription;
	local string strOperateType;

	local int iMPConsume;
	local int iCastRange;
	local INT64 iNumOfItem;

	local string strEnchantName;
	local string strEnchantDesc;

	local int iPercent;
	
	local ClanDrawerWnd script;


	switch(Event_ID)
	{
	case EV_SkillTrainInfoWndShow :
		ParseInt(param, "Type", iType);
	
		script = ClanDrawerWnd( GetScript("ClanDrawerWnd") );
		class'UIDATA_CLAN'.static.RequestClanInfo();	
		script.InitializeClanInfoWnd();
	
		if (iType == ESTT_SUB_CLAN)	// 하위부대 스킬이 아닐경우 아무것도 하지 않는다. 
		{
			
			
			ParseString(param, "strIconName", strIconName); 
			ParseString(param, "strName", strName);
			ParseInt(param, "iID", iID);
			ParseInt(param, "iLevel", iLevel);
			ParseString(param, "strOperateType", strOperateType);
			ParseInt(param, "iMPConsume", iMPConsume);
			ParseInt(param, "iCastRange", iCastRange);
			ParseInt(param, "iSPConsume", iSPConsume);
			ParseString(param, "strDescription", strDescription);
			ParseInt64(param, "iEXPConsume", iEXPConsume);
			ParseString(param, "strEnchantName", strEnchantName);
			ParseString(param, "strEnchantDesc", strEnchantDesc);
			ParseInt(param, "iPercent", iPercent);
	
			m_iType=iType;
			m_iID=iID;
			m_iLevel=iLevel;
	
			ShowSkillTrainClanTreeWnd();
			
			DisableAllClanTreeWnd();
			EnableProperClanSubWnd( iID, iLevel);
			AddSkillTrainInfo(strIconName, strName, iID, iLevel, strOperateType, iMPConsume, iCastRange, strDescription, iSPConsume, iEXPConsume, strEnchantName, strEnchantDesc, iPercent);
		}
		break;

	case EV_SkillTrainInfoWndAddExtendInfo:
		if( m_iType == ESTT_SUB_CLAN)	// EV_SkillTrainInfoShow가 들어오지 않으면 아무것도 하지 안음
		{
			ParseString(param, "strIconName", strIconName); 
			ParseString(param, "strName", strName);
			ParseINT64(param, "iNumOfItem", iNumOfItem);
			
			AddSkillTrainInfoExtend(strIconName, strName, iNumOfItem);
			ShowNeedItems();
		}
		break;

	case  EV_SkillTrainInfoWndHide :
		if( m_iType == ESTT_SUB_CLAN)	// EV_SkillTrainInfoShow가 들어오지 않으면 아무것도 하지 안음
		{
			if(IsShowWindow("SkillTrainClanTreeWnd"))
				HideWindow("SkillTrainClanTreeWnd");
		}
		break;
		
	case EV_GamingStateExit:	//나갈때 초기화해주기
		Load();
		Initialize();
		break;
	}
}

//적절한 하위 부대의 윈도우를 인에이블 시켜준다. 
function EnableProperClanSubWnd(int iID, int iLevel)
{
	local int i;
	local int nClanIdx;
	local int LearnSkillLV;
	
	for( i=0 ; i < CLAN_KNIGHTHOOD_COUNT ; ++i )
	{
		nClanIdx = GetClanTypeFromIndex( i );	// 클랜 인덱스 가져오기
		LearnSkillLV = class'UIDATA_CLAN'.static.GetSubClanSkillLevel(iID,nClanIdx);	// 해당 하위 부대가 해당 스킬을 몇레벨 까지 배웠는지 가져온다.
		
		//debug(" iID : " $ iID $ " nClanIdx : " $ nClanIdx $ " LearnSkillLV : " $ LearnSkillLV $ " iLevel : " $ iLevel );
		
		if(nClanIdx == CLAN_ACADEMY) 
		{
			return;	// 아카데미는 무시한다. 
		}
		
		//배울수 있는 하위부대만 인에이블 된다. 
		if(LearnSkillLV == -1 && iLevel == 1)	
		{
			Clan_OrgIcon[i].EnableWindow();
			Clan_OrgIcon[i].SetAlpha(255);
		}
		else if( LearnSkillLV  == iLevel -1 )
		{
			Clan_OrgIcon[i].EnableWindow();
			Clan_OrgIcon[i].SetAlpha(255);
		}
	}
}


// 스킬 트레이닝 윈도우 초기화 시킴
function ShowSkillTrainClanTreeWnd()
{
	local UserInfo infoPlayer;

	GetPlayerInfo(infoPlayer);

	//class'UIDATA_CLAN'.static.RequestClanInfo();	// 정보 요청
		
	class'UIAPI_WINDOW'.static.SetWindowTitle("SkillTrainClanTreeWnd", 1436);					// 윈도 타이틀 변경
	//SetBackTex("L2UI_CH3.SkillTrainWnd.SkillTrain2");									// 9등분할을 적용하면서 삭제
	class'UIAPI_TEXTBOX'.static.SetText("SkillTrainClanTreeWnd.txtSPString", GetSystemString(1372));	// SP or 혈맹명성치 글씨
	class'UIAPI_TEXTBOX'.static.SetInt("SkillTrainClanTreeWnd.txtSP", GetClanNameValue(infoPlayer.nClanID));						// SP or 혈맹명성치
	
	ShowWindowWithFocus("SkillTrainClanTreeWnd");
}

function AddSkillTrainInfo(string strIconName, string strName, int iID, int iLevel, string strOperateType, int iMPConsume, int iCastRange, string strDescription, int iSPConsume, INT64 iEXPConsume, string strEnchantName, string strEnchantDesc, int iPercent)
{
	// 아이템 아이콘
	class'UIAPI_TEXTURECTRL'.static.SetTexture("SkillTrainClanTreeWnd.texIcon", strIconName);
	// 스킬이름
	class'UIAPI_TEXTBOX'.static.SetText("SkillTrainClanTreeWnd.txtName", strName);

	// level 숫자
	class'UIAPI_TEXTBOX'.static.SetInt("SkillTrainClanTreeWnd.txtLevel", iLevel);
	// 작동타입
	class'UIAPI_TEXTBOX'.static.SetText("SkillTrainClanTreeWnd.txtOperateType", strOperateType);
	// 소모MP
	class'UIAPI_TEXTBOX'.static.SetInt("SkillTrainClanTreeWnd.txtMP", iMPConsume);
	// 스킬설명 
	class'UIAPI_TEXTBOX'.static.SetText("SkillTrainClanTreeWnd.txtDescription", strDescription);

	class'UIAPI_TEXTBOX'.static.SetText("SkillTrainClanTreeWnd.txtNeedSPString", GetSystemString(1437));
	// 필요SP 숫자
	class'UIAPI_TEXTBOX'.static.SetInt("SkillTrainClanTreeWnd.txtNeedSP", iSPConsume);
	
	if(iCastRange>=0)
	{
		// 보여주고
		ShowWindow("SkillTrainClanTreeWnd.txtCastRangeString");
		ShowWindow("SkillTrainClanTreeWnd.txtColoneCastRange");
		class'UIAPI_TEXTBOX'.static.SetInt("SkillTrainClanTreeWnd.txtCastRange", iCastRange);
	}
	else
	{
		// 숨겨준다
		HideWindow("SkillTrainClanTreeWnd.txtCastRangeString");
		HideWindow("SkillTrainClanTreeWnd.txtColoneCastRange");
		HideWindow("SkillTrainClanTreeWnd.txtCastRange");
	}
}

function AddSkillTrainInfoExtend(string strIconName, string strName, INT64 iNumOfItem)
{
	// 아이템 아이콘
	texNeedItemIcon.SetTexture(strIconName);
	// 스킬이름
	txtNeedItemName.SetText(strName$" X "$ Int64ToString(iNUmOfItem));
}

function OnShow()
{
	//ShowWindow("SkillTrainClanTreeWnd.SubWndNormal");
	// 필요한 아이템 정보 숨겨준다
	HideWindow("SkillTrainClanTreeWnd.SubWndNormal.texNeedItemIcon");
	HideWindow("SkillTrainClanTreeWnd.SubWndNormal.txtNeedItemName");
}

function ShowNeedItems()
{
	// 아이템아이콘과 이름
	ShowWindow("SkillTrainClanTreeWnd.texNeedItemIcon");
	ShowWindow("SkillTrainClanTreeWnd.txtNeedItemName");
}

// 9등분할을 적용하면서 삭제
/*
function SetBackTex(string strFile)
{
	class'UIAPI_TEXTURECTRL'.static.SetTexture("SkillTrainClanTreeWnd.texBack", strFile);
}
*/

function int GetClanTypeFromIndex( int index )
{
	local int type;
	if( index == 0 )
	{
		type = CLAN_MAIN;
	}
	if( index == 1 )
	{
		type = CLAN_KNIGHT1;
	}
	if( index == 2 )
	{
		type = CLAN_KNIGHT2;
	}
	if( index == 3 )
	{
		type = CLAN_KNIGHT3;
	}
	if( index == 4 )
	{
		type = CLAN_KNIGHT4;
	}
	if( index == 5 )
	{
		type = CLAN_KNIGHT5;
	}
	if( index == 6 )
	{
		type = CLAN_KNIGHT6;
	}
	if( index == 7 )
	{
		type = CLAN_ACADEMY;
	}
	return type;
}

defaultproperties
{
}
