class RadarMapWnd extends UICommonAPI;

const TIMER_ID1 = 123;	//  radar map object  refresh timer
const TIMER_DELAY1 = 2000;	// 1700ms refresh //정보 갱신을 요청하는 것은 이 micro second의 영향을 받는다. 

const TIMER_ID2	= 124;	// local map move timer
const TIMER_ID2_LARGE = 130; // local map move timer (For MAPS WITH LARGE FLAG)
const TIMER_DELAY2 = 50;	// Local map move timer

const TIMER_ID3 = 125;	// no_map text show timer
const TIMER_DELAY3 = 3000;

const TIMER_ZOOM_IN = 126;
const TIMER_ZOOM_OUT = 127;

const TIMER_DELAY_ZOOM = 25;	// zoomin/zoomout speed

const TIMER_SHOW_RESIZE = 128;

const TIMER_ZONE_NAME = 129;

const TARGET_RADAR_ID = 7777;			//타겟 아이디를 미리 하나 할당해둠
const QUEST_TARGET_RADAR_ID = 777;

const FADEIN_HIGHLIGHT_TIMER_ID = 22222;
const DELAY_HIGHLIGHT_PLAYER_TIMER_ID = 11111;

const MAX_MONSTER = 40;
const MAX_PARTY= 8;

const HIGHLIGHT_ACCESSORY = "LineageEffectWD27.pnt_drop_accessary";
const HIGHLIGHT_ARMOR = "LineageEffectWD27.pnt_drop_armor";
const HIGHLIGHT_WEAPON = "LineageEffectWD27.pnt_drop_weapon";

//const MAG_1= 1.5;		//old values
//const MAG_2= 1.3;
//const MAG_3= 1.0;
//const MAG_4= 0.7;
//const MAG_5= 0.15; 

const MAG_1 = 1.0;	
const MAG_2 = 0.25;
const MAG_3 = 0.15;
const MAG_4 = 0.1;
const MAG_5 = 0.085; 

const ALPHA_1 = 40;	
const ALPHA_2 = 40;
const ALPHA_3 = 120;
const ALPHA_4 = 180;
const ALPHA_5 = 230; 

const COEFF_1 = 120;	//1.0
const COEFF_2 = 32;		//0.25
const COEFF_3 = 20;		//0.15
const COEFF_4 = 13;		//0.1
const COEFF_5 = 11; 	//0.085

const MIN_MAG= 0;
const MAX_MAG= 4;

const FS_TIME = 45000;	                // 시스템 튜토리얼이 랜덤하게 보이는 시간	//45초에 1회씩
const FS_TIMER_ID = 151;	            // 타이머 아이디

const EDGE_RED = 8;
const EDGE_BUFFRED = 9;
const EDGE_ORANGE = 11;
const EDGE_BLUE = 12;
const EDGE_SSQGRAY = 13;
const EDGE_PVPGREEN = 14;
const EDGE_GRAY = 15;


struct npcObject
{
	var int 	ID;
	var string	Name;
	var string	titleName;
	var string	iconType;
	var Vector	Location;
	var bool	isMonster;
};

var npcObject	radarObject[MAX_MONSTER];	//40

//var int farestDistance;	// samii dalnii mob
//var int farestIndex;	// index samogo dalnego moba
var int dynamicCount;	// dinamicheski izmenyaem radius scana actorov

var int questID;
var int questLevel;

var int currentStepping; // tekushee  kolicheestvo etapov priblijeniya pri zoome
var int zoomStepping;	// vsego kolicheestvo etapov priblijeniya pri zoome

var string ZoneTooltipArea;
var Color ZoneTooltipColor;

var string m_TargetName;		     // 타겟 정보를 임시 저장한다. 
var	vector m_TargetPosition;

var vector MyPosition;
var int m_TargetID;
var int my_ID;

var Rect LocalMapRect;

//var int ObjectID[MAX_MONSTER];	 // 몬스터의 ID를 저장하는 배열

var int membersCount;	// current party size in foreach
var int membersPetCount;

var int partyCount;		// total party size
var int partyPetCount;

var int arr_PartyID[MAX_PARTY];		 // 파티원의 ID를 저장하는 배열
var int arr_PartyPetID[MAX_PARTY];
var int arr_PartyLocX[MAX_PARTY];	 // 파티원의 위치정보
var int arr_PartyLocY[MAX_PARTY];	 // 파티원의 위치정보
var int arr_PartyLocZ[MAX_PARTY];	 // 파티원의 위치정보
var string arr_PartyName[MAX_PARTY]; // 파티원의 이름

var float mag;
var string localMag;
var float arrMag[5];
var int arrAlpha[5];		// prozrachnost' texturi  dlya effekta potemneniya/osvetleniya pri zoome
var int arrCoefficient[5];	// sootnoshenie razmera k Magnification (podbiralos' na glazok)
var int magStep;	
var int prevmagStep;

var bool show;
var int LocalMapX;
var int LocalMapY;
var string LocalMapName;




var int fixedRangeDrawX[5];
var int fixedRangeDrawY[5];
var int freeRangeDrawXY[5];

var int fixedScanRange[5];
var int freeScanRange[5];

var int rectangleMapSizeX;
var int rectangleMapSizeY;
var int squareMapSizeXY;

var int zoneState;

var bool isCanScan;
var bool showMonster;
var bool hideParty;
var bool inParty;		    // 현재 파티에 속해있는지 확인
var bool inGamingState;		// 현재 게임 스테이트인지 확인
var bool showMe;	        // 내 위치 표시 보이기/ 감추기
var bool fixRadar;	        // 레이더 고정

var bool isOnFSTimer;	    // 시스템 튜토리얼이 켜져있는지 확인
var bool isFreeShip;

var bool isVisibleTarget;

var bool showRadar;


////////////// DROP HIGHLGIGHT ////////////
var bool showDropHighlight;
var bool HideDropItem;	// from OptionWnd

var class<Emitter> HLEmitterClassWeapon;
var class<Emitter> HLEmitterClassArmor;
var class<Emitter> HLEmitterClassAccessory;
var class<Emitter> PartyPetHLEmitterClass;

///////////////////////////////////

//var WindowHandle		wnd_Crown;

var bool highlightMode;		//common mode
var bool highlightMe;
var bool highlightPet;
var bool highlightScale;
var bool highlightPartyMember;	// 
var bool highlightPartyLeader;
var bool highlightPartyPet;
var bool highlightUnionLeader;

var class<Emitter> PartyMemberHLEmitterClass;
var class<Emitter> PartyLeaderHLEmitterClass;
var class<Emitter> UnionLeaderHLEmitterClass;
var class<Emitter> PartyPetMemberHLEmitterClass;


var string HIGHLIGHT_PARTY_MEMBER_EFFECT;
var string HIGHLIGHT_PARTY_LEADER_EFFECT;
var string HIGHLIGHT_UNION_LEADER_EFFECT;
var string HIGHLIGHT_PARTY_PET_EFFECT;

var rotator defaultRotion;

var int partyLeaderID;
var int unionLeaderID;
var string unionLeaderName;		// to detect new union leader

var bool haveUnionLeader;		// to detect current player mount/dismount state 
var bool isFadeInStart;			// to test fade-in highlight mode
//var Emitter EffectViewerEmitter;

///////////////////////////////////

var RadarMapCtrlHandle	rdr_RadarMapTex;	// for map
var RadarMapCtrlHandle	rdr_RadarMapObject;	// for object

var WindowHandle		wnd_RadarMap;
var WindowHandle		wnd_RadarMapRotation;
var WindowHandle		wnd_SquareMask;
var WindowHandle		wnd_RadarSettings;
var WindowHandle 		wnd_ResizeArea;	// 400x400 detect drop item window
var WindowHandle 		wnd_RectangleMask;


///////////// Mouse Over Detect //////////////////
var TextureHandle		tex_DetectLeft;
var TextureHandle		tex_DetectRight;
var TextureHandle		tex_DetectUp;
var TextureHandle		tex_DetectDown;
var TextureHandle		tex_DetectCenter;

//////////////// radar static elements	//////////////////
var TextureHandle		tex_rangeRadiusBig;
var TextureHandle		tex_rangeRadiusSmall;
var TextureHandle		tex_Compas;
var TextureHandle		tex_myPosition;
var TextureHandle		tex_myAngle;
var TextureHandle		tex_seaBg;
var	ItemWindowHandle	itm_ResizeArrow;

var TextureHandle		tex_ControlFrame;	// draggable control texture
//var TextureHandle		tex_Gradient;
var TextureHandle		tex_BackAlpha;		// dlya effekta zatemnenie pri zoome
var TextureHandle		tex_Slider;


var TextureHandle		tex_ZoneIcon;
var TextureHandle		tex_ZoneName;
var TextureHandle		tex_LocalMap; // textura karti gorodov i t.d.

//var TextureHandle		tex_SquareMask;		// square mask
//var TextureHandle		tex_RectangleMask;

//////////// Buttons /////////////////
var ButtonHandle		btn_FixedOn;
var ButtonHandle		btn_FixedOff;

var ButtonHandle		btn_ShowMobOn;
var ButtonHandle		btn_ShowMobOff;

var ButtonHandle		btn_ShowPartyOn;
var ButtonHandle		btn_ShowPartyOff;

var ButtonHandle		btn_ShowMeOn;
var ButtonHandle		btn_ShowMeOff;

var ButtonHandle		btn_AlfaOn;
var ButtonHandle		btn_AlfaOff;

var ButtonHandle		btn_Plus;
var ButtonHandle		btn_Minus;

var ButtonHandle		btn_MyTeleport;

var TextBoxHandle		txt_boxMove;
var TextBoxHandle		txt_noMap;

// 비행정 관련 핸들
var	WindowHandle		FlightStatusGauges;
var StatusBarHandle		barFuel;
var BarHandle			barMP;
var BarHandle			barHP;
var TextBoxHandle		ShipNameTxt;	     // 비행정 이름 "%s 혈맹의 비행정"

function OnLoad()
{
	local int temp;
	local string tmpName;
	local string tmpEffectName;
//	RegisterState( "RadarMapWnd", "DebugState");
	
	wnd_RadarMap = GetWindowHandle("RadarMapWnd");
	
	rdr_RadarMapTex = GetRadarMapCtrlHandle("RadarMapWnd.RadarMapTex");
	rdr_RadarMapObject = GetRadarMapCtrlHandle("RadarMapWnd.RadarMapObject");
	
	wnd_RadarMapRotation = GetWindowHandle("RadarMapWnd.RadarMapRotationWnd");
	wnd_SquareMask = GetWindowHandle("RadarMapWnd.RadarMapRotationWnd.SquareMask");
	wnd_RectangleMask = GetWindowHandle("RadarMapWnd.RadarMapRotationWnd.RectangleMask");
	
	wnd_ResizeArea = GetWindowHandle("RadarMapWnd.RadarMapRotationWnd.wndResizeArea");

	wnd_RadarSettings = GetWindowHandle("RadarMapWnd.RadarMapSettings");

	tex_seaBg = GetTextureHandle ("RadarMapWnd.texSeaBg");
	tex_rangeRadiusBig = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texRangeRadiusBig");
	tex_rangeRadiusSmall = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texRangeRadiusSmall");
	tex_Compas = GetTextureHandle ("RadarMapWnd.texCompasN");
	tex_myPosition = GetTextureHandle ("RadarMapWnd.texMyPosition");
	tex_myAngle = GetTextureHandle ("RadarMapWnd.texMyAngle");
	
	txt_noMap = GetTextBoxHandle("RadarMapWnd.txtNoMap");
	txt_boxMove = GetTextBoxHandle("RadarMapWnd.txtBoxMove");

		
		// 비행정 게이지 관련 핸들 초기화
	FlightStatusGauges = GetWindowHandle( "RadarMapWnd.FlightStatusGauges" );
	barFuel = GetStatusBarHandle( "RadarMapWnd.FlightStatusGauges.barFuel" );
	barMP = GetBarHandle( "RadarMapWnd.FlightStatusGauges.barMP" );
	barHP = GetBarHandle( "RadarMapWnd.FlightStatusGauges.barHP" );
	ShipNameTxt = GetTextBoxHandle ( "RadarMapWnd.FlightStatusGauges.ShipNameTxt" );
		
		
	tex_LocalMap = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.SquareMask.texLocalMap");
//	tex_SquareMask = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.SquareMask.texSquareMask");
//	tex_RectangleMask = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.RectangleMask.texRectangleMask");

	tex_DetectLeft = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texDetectLeft");
	tex_DetectRight = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texDetectRight");
	tex_DetectUp = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texDetectUp");
	tex_DetectDown = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texDetectDown");
	tex_DetectCenter = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texDetectCenter");
	tex_ControlFrame = GetTextureHandle ("RadarMapWnd.texControlFrame");
	tex_BackAlpha = GetTextureHandle ("RadarMapWnd.RadarMapRotationWnd.texBackAlpha");
	tex_Slider = GetTextureHandle ("RadarMapWnd.texSlider");
	
//	tex_Gradient = GetTextureHandle ("RadarMapWnd.RadarMapSettings.texRadarGradient");
	tex_ZoneIcon = GetTextureHandle ("RadarMapWnd.texZoneIcon");
	tex_ZoneName = GetTextureHandle ("RadarMapWnd.texZoneName");
		
	////////// Button handle //////////
	btn_FixedOn = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnFixedOn");
	btn_FixedOff = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnFixedOff");
	
	btn_ShowMobOn = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnShowMobOn");
	btn_ShowMobOff = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnShowMobOff");
	
	btn_ShowPartyOn = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnShowPartyOn");
	btn_ShowPartyOff = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnShowPartyOff");
	
	btn_ShowMeOn = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnShowMeOn");
	btn_ShowMeOff = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnShowMeOff");
	
	btn_AlfaOn = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnAlfaOn");
	btn_AlfaOff = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnAlfaOff");
	
	btn_Plus = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnPlus");
	btn_Minus = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnMinus");
	
	btn_MyTeleport = GetButtonHandle("RadarMapWnd.RadarMapSettings.btnTeleport");

	itm_ResizeArrow = GetItemWindowHandle("RadarMapWnd.RadarMapRotationWnd.itmResizeArrow");

	////////// Tooltip //////////
	btn_AlfaOn.SetTooltipCustomType(MakeTooltipSimpleText("Transparency"));
	btn_AlfaOff.SetTooltipCustomType(MakeTooltipSimpleText("Transparency"));
	btn_Plus.SetTooltipCustomType(MakeTooltipSimpleText("Zoom In"));
	btn_Minus.SetTooltipCustomType(MakeTooltipSimpleText("Zoom Out"));
	btn_FixedOn.SetTooltipCustomType(MakeTooltipSimpleText("Fixed Radar"));
	btn_FixedOff.SetTooltipCustomType(MakeTooltipSimpleText("Fixed Radar"));	
	btn_ShowMobOn.SetTooltipCustomType(MakeTooltipSimpleText("Monsters"));
	btn_ShowMobOff.SetTooltipCustomType(MakeTooltipSimpleText("Monsters"));	
	btn_ShowPartyOn.SetTooltipCustomType(MakeTooltipSimpleText("Show Party"));
	btn_ShowPartyOff.SetTooltipCustomType(MakeTooltipSimpleText("Show Party"));
	btn_ShowMeOn.SetTooltipCustomType(MakeTooltipSimpleText("Show Player"));
	btn_ShowMeOff.SetTooltipCustomType(MakeTooltipSimpleText("Show Player"));
	btn_MyTeleport.SetTooltipCustomType(MakeTooltipSimpleText("My Teleport"));
	
	mag = MAG_1;
	magStep = 0;
	prevmagStep = magStep;
	
	
	isFreeShip = false;
	FlightGaugesClear();	// 비행정 관련 게이지 초기화
	
	wnd_ResizeArea.HideWindow();
	

	tex_ZoneName.HideWindow();
	tex_ControlFrame.HideWindow();
	tex_DetectDown.HideWindow();
	tex_DetectUp.HideWindow();
	tex_DetectLeft.HideWindow();
	tex_DetectRight.HideWindow();
	tex_DetectCenter.ShowWindow();
	

	showMonster = GetOptionBool("Game", "radarShowMonster");
	hideParty = GetOptionBool("Game", "radarHideParty");
	showMe = GetOptionBool("Game", "radarShowMe");
	fixRadar = GetOptionBool("Game", "radarFix");
	
	GetINIBool("RadarMap", "TransparencyOn", temp, "PatchSettings");
	if (temp == 1)
		OnClickAlfaOffButton();
	else
		OnClickAlfaOnButton();


	GetINIBool("WindowsCheks", "RadarMapWnd", temp, "PatchSettings");
	showRadar = false;
	if (temp == 1)
	{
		showRadar = true;
	}

	
	if (showMonster == true)
	{
		btn_ShowMobOn.ShowWindow();
		btn_ShowMobOff.HideWindow();
	}
	else
	{
		btn_ShowMobOn.HideWindow();
		btn_ShowMobOff.ShowWindow();
	}

	if (fixRadar == true)	
	{
		btn_FixedOn.ShowWindow();
		btn_FixedOff.HideWindow();
	}
	else	
	{
		btn_FixedOn.HideWindow();
		btn_FixedOff.ShowWindow();
	}	

		
	if (hideParty == true)
	{
		btn_ShowPartyOn.HideWindow();
		btn_ShowPartyOff.ShowWindow();
	}
	else
	{
		btn_ShowPartyOn.ShowWindow();
		btn_ShowPartyOff.HideWindow();
	}

	if (showMe == true)
	{
		btn_ShowMeOn.ShowWindow();
		btn_ShowMeOff.HideWindow();
		tex_myPosition.ShowWindow();
	}
	else
	{
		btn_ShowMeOn.HideWindow();
		btn_ShowMeOff.ShowWindow();
		tex_myPosition.HideWindow();
	}

	arrMag[0] = MAG_1;
	arrMag[1] = MAG_2;
	arrMag[2] = MAG_3;
	arrMag[3] = MAG_4;
	arrMag[4] = MAG_5;

	arrAlpha[0] = ALPHA_1;
	arrAlpha[1] = ALPHA_2;
	arrAlpha[2] = ALPHA_3;
	arrAlpha[3] = ALPHA_4;
	arrAlpha[4] = ALPHA_5;

	arrCoefficient[0] = COEFF_1;
	arrCoefficient[1] = COEFF_2;
	arrCoefficient[2] = COEFF_3;
	arrCoefficient[3] = COEFF_4;
	arrCoefficient[4] = COEFF_5;
	
	inGamingState = false;
	show = false;
	isCanScan = false;
	
	rdr_RadarMapTex.SetMagnification(mag);
	rdr_RadarMapObject.SetMagnification(mag);

	InitDatas();
	
	SetSliderPos();
	
	RecoveryRadar();
	
	
	HideDropItem = false;
	HideDropItem = GetOptionBool( "Game", "HideDropItem");

	tmpName = "";
	GetINIString("Highlight", "HighlightDrop", tmpName, "HighlightSettings");
	if (tmpName == "True")
	{
		showDropHighlight = true;
	}
	else
	{
		showDropHighlight = false;
	}
	
	defaultRotion.Yaw = 0;
	defaultRotion.Pitch = 0;
	defaultRotion.Roll = 0;

	HLEmitterClassWeapon = class<Emitter>(DynamicLoadObject(HIGHLIGHT_WEAPON, class'Class'));
	HLEmitterClassArmor = class<Emitter>(DynamicLoadObject(HIGHLIGHT_ARMOR, class'Class'));
	HLEmitterClassAccessory = class<Emitter>(DynamicLoadObject(HIGHLIGHT_ACCESSORY, class'Class'));
	
//	HIGHLIGHT_PARTY_MEMBER_EFFECT = 'e_u520_identifier_b';br_e_u107_totem_of_mind

	partyLeaderID = -1;
	unionLeaderID = -1;
	unionLeaderName = "";

	tmpName = "";
	GetINIString("Highlight", "HighlightMode", tmpName, "HighlightSettings");
	if (tmpName == "True")
	{
		highlightMode = true;
	}
	else
	{
		highlightMode = false;
	}

	tmpName = "";
	GetINIString("Highlight", "HighlightMe", tmpName, "HighlightSettings");
	if (tmpName == "True")
	{
		highlightMe = true;
	}
	else
	{
		highlightMe = false;
	}

	tmpName = "";
	GetINIString("Highlight", "HighlightPet", tmpName, "HighlightSettings");
	if (tmpName == "True")
	{
		highlightPet = true;
	}
	else
	{
		highlightPet = false;
	}
	
	tmpName = "";
	GetINIString("Highlight", "highlightScale", tmpName, "HighlightSettings");
	if (tmpName == "True")
	{
		highlightScale = true;
	}
	else
	{
		highlightScale = false;
	}

	tmpName = "";
	tmpEffectName = "";
	GetINIString("Highlight", "PartyMember", tmpName, "HighlightSettings");
	if (tmpName == "" || tmpName == "0")
	{
		highlightPartyMember = false;
		if (tmpName == "")
			SetINIString("Highlight", "PartyMember", "0", "HighlightSettings");
	}
	else
	{
		highlightPartyMember = true;
		GetINIString(string(int(tmpName) - 1), "EffectName", tmpEffectName, "HighlightSettings");
		HIGHLIGHT_PARTY_MEMBER_EFFECT = Right(tmpEffectName, Len(tmpEffectName) - InStr(tmpEffectName, ".") - 1);
		PartyMemberHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpEffectName, class'Class'));
	}

	tmpName = "";
	tmpEffectName = "";
	GetINIString("Highlight", "PartyLeader", tmpName, "HighlightSettings");
	if (tmpName == "" || tmpName == "0")
	{
		highlightPartyLeader = false;
		if (tmpName == "")
			SetINIString("Highlight", "PartyLeader", "0", "HighlightSettings");
	}
	else
	{
		highlightPartyLeader = true;
		GetINIString(string(int(tmpName) - 1), "EffectName", tmpEffectName, "HighlightSettings");
		HIGHLIGHT_PARTY_LEADER_EFFECT = Right(tmpEffectName, Len(tmpEffectName) - InStr(tmpEffectName, ".") - 1);
		PartyLeaderHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpEffectName, class'Class'));
	}

	tmpName = "";
	tmpEffectName = "";
	GetINIString("Highlight", "PartyPet", tmpName, "HighlightSettings");
	if (tmpName == "" || tmpName == "0")
	{
		highlightPartyPet = false;
		if (tmpName == "")
			SetINIString("Highlight", "PartyPet", "0", "HighlightSettings");
	}
	else
	{
		highlightPartyPet = true;
		GetINIString(string(int(tmpName) - 1), "EffectName", tmpEffectName, "HighlightSettings");
		HIGHLIGHT_PARTY_PET_EFFECT = Right(tmpEffectName, Len(tmpEffectName) - InStr(tmpEffectName, ".") - 1);
		PartyPetHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpEffectName, class'Class'));
	}
	
	tmpName = "";
	tmpEffectName = "";
	GetINIString("Highlight", "UnionLeader", tmpName, "HighlightSettings");
	if (tmpName == "" || tmpName == "0")
	{
		highlightUnionLeader = false;
		if (tmpName == "")
			SetINIString("Highlight", "UnionLeader", "0", "HighlightSettings");
	}
	else
	{
		highlightUnionLeader = true;
		GetINIString(string(int(tmpName) - 1), "EffectName", tmpEffectName, "HighlightSettings");
		HIGHLIGHT_UNION_LEADER_EFFECT = Right(tmpEffectName, Len(tmpEffectName) - InStr(tmpEffectName, ".") - 1);
		UnionLeaderHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpEffectName, class'Class'));
	}
	
//	if (highlightPartyMember || highlightPartyLeader || highlightUnionLeader)
//		highlightMode = true;
//	else
//		highlightMode = false;
}


function OnRegisterEvent()
{
	RegisterEvent(EV_SetRadarZoneCode);
	RegisterEvent( EV_BeginShowZoneTitleWnd );		//존네임이 바뀔 때, 레이더를 가려줄지 말지를 결정해야한다. 
	
	RegisterEvent( EV_PartyAddParty); 
	RegisterEvent( EV_PartyDeleteParty);
	RegisterEvent( EV_PartyDeleteAllParty);
	
	RegisterEvent( EV_GamingStateEnter );
	RegisterEvent( EV_GamingStateExit );
	
	RegisterEvent( EV_TargetUpdate );				//타겟 업데이트 될 경우 타겟을 표시해준다.
	
	RegisterEvent( EV_AirShipState );	// 비행정 스테이트가 들어왔을 경우
	RegisterEvent( EV_AirShipUpdate );	// 비행정 정보 업데이트 이벤트
	
	////////////// ADDED FOR HIGHLIGHT PARTY STUFF ////////////////////////
	RegisterEvent(EV_UpdateUserInfo);
	
	RegisterEvent(EV_Die);

	RegisterEvent(EV_CommandChannelPartyList);		// to detect union changed
	RegisterEvent(EV_CommandChannelInfo);		// to detect union changed
	RegisterEvent(EV_CommandChannelEnd);			// to detect union changed
	
	RegisterEvent(EV_PartySummonAdd);
	RegisterEvent(EV_PartySummonDelete);
	
	RegisterEvent(EV_SummonedStatusShow);
	RegisterEvent(EV_PetStatusShow);
	////////////// ADDED FOR HIGHLIGHT PARTY STUFF ////////////////////////
}


function OnEvent( int a_EventID, String a_Param )
{
	// 움직이는 텍스트 표시
	local int type;

	switch( a_EventID )
	{
		case EV_UpdateUserInfo:
			if	(my_ID <= 0) 
			{
				my_ID = class'UIDATA_PLAYER'.static.GetPlayerID();
				if (my_ID > 0)  
				{
					isCanScan = true;
					partyLeaderID = my_ID;
					wnd_RadarMap.SetTimer(TIMER_ID1, TIMER_DELAY1);			
				}
			}
			else
			{
				my_ID = class'UIDATA_PLAYER'.static.GetPlayerID();
				ChangeMyPawn();
			}
			break;	
			
		case EV_SetRadarZoneCode:
			ParseInt( a_Param, "ZoneCode", type );
			HandleRadarZoneCode(type);
			break;
		
		case EV_PartyAddParty:
			HandlePartyAddParty(a_Param);
			break;
		
		case EV_PartyDeleteParty:	// Someone dismissed party
			HandlePartyDeleteParty(a_Param);
			break;
		
		case EV_PartyDeleteAllParty:	//  Me dismissed party
			CleanMyHighlight();
			CleanAllPartyHighlight();
			HandlePartyDeleteAllParty();
			break;
					
		case EV_GamingStateEnter:
			zoneState = 0;
			inGamingState = true;
			isFadeInStart = false;
			HandleZoneTitle();	// dlya detecta na reloge togo je chara;
			break;
		
		case EV_GamingStateExit:
			inGamingState = false;
			ClearObject();
			InitDatas();
			StopLocal();
			my_ID = -1;
			isCanScan = false;
			break;
			
		case EV_Die:
			HighlightMyPawn();
			break;
		
		case EV_BeginShowZoneTitleWnd:
			HandleZoneTitle();
			break;
		
		case EV_TargetUpdate:
			HandleTargetUpdate();
			break;
		
		case EV_AirShipState:
			OnAirShipState( a_Param );
			break;
		
		case EV_AirShipUpdate:
			OnAirShipUpdate( a_Param );
			break;
			
		case EV_CommandChannelInfo:
			HandleCommandChannelInfo(a_Param);
			break;
		
		case EV_CommandChannelPartyList:
			HandleCommandChannelPartyList(a_Param);
			break;		
			
		case EV_CommandChannelEnd:
			if (highlightUnionLeader)
				CleanPawnHighlight(unionLeaderID);
			unionLeaderID = -1;
			unionLeaderName = "";
			break;
			
		case EV_PartySummonAdd:
			HandlePartySummonAdd(a_Param);
			break;
			
		case EV_PartySummonDelete:
			HandlePartySummonDelete(a_Param);
			break;

		case EV_PetStatusShow:
		case EV_SummonedStatusShow:
			HighLightMyPetPawn();
			break;
	}
}


function HandlePartySummonDelete( string param )
{
	local int	SummonID;
	local int	idx;
	
	ParseInt(param, "SummonID", SummonID);
	
	if (SummonID > 0)
	{
		idx = FindPetID(SummonID);
		if (idx > -1)
		{	
			if (highlightPartyPet)
				CleanPawnHighlight(SummonID, true);
			arr_PartyPetID[idx] = -1;
			partyPetCount--;
		}
	}
}


function HandlePartySummonAdd( string param )
{
	local int	SummonMasterID;
	local int	SummonID;
	local int	MasterIndex;
	
	
	ParseInt(param, "SummonMasterID", SummonMasterID);	// ??? ID? ????.
	ParseInt(param, "SummonID", SummonID);	// ??? ID? ????.
	
	if (SummonMasterID > 0)
	{	
		MasterIndex = -1;
		MasterIndex = FindPartyIDX(SummonMasterID);

		if(MasterIndex == -1)
		{
			return;
		}
		
		if (highlightMode)
			if (highlightPartyPet)
				HighlightPawnInstant(SummonID, true, true);
		
		if (arr_PartyPetID[MasterIndex] != SummonID)		// on gm speed sumommons re-spawn without HandlePartySummonDel event, so check it
		{
			partyPetCount++;
			arr_PartyPetID[MasterIndex] = SummonID;
		}
	}
}


function int FindPetID(int ID)
{
	local int idx;
	
	for (idx = 0; idx < MAX_PARTY; idx++)
	{
		if (arr_PartyPetID[idx] == ID)
		{
			return idx;
		}
	}
	return -1;
}


function HandleCommandChannelInfo(string param)
{
	ParseString(param, "OwnerName" , unionLeaderName);
}

function HandleCommandChannelPartyList(string param)
{
	local string MasterName;
	local int MasterID;
	
	ParseString(param, "MasterName", MasterName);

	if (MasterName == unionLeaderName)
	{	
		ParseInt(param, "MasterID", MasterID);
		
		if (unionLeaderID > 0)
			if (MasterID == unionLeaderID)
				return;
		
		if (unionLeaderID != -1)		// clean previous ally leader highlight
			if (highlightMode)
				if (highlightUnionLeader)
					CleanPawnHighlight(unionLeaderID);
		
		unionLeaderID = MasterID;
		
		if (highlightMode)	// highlight new ally leader 
			if (highlightUnionLeader)
				HighlightPawnInstant(unionLeaderID, , true);
	}
}


function ChangeMyPawn()
{
	local UserInfo PlayerInfo;
	local Pawn PlayerPawn;

	if (inParty && highlightMe && highlightMode)
	{
		
		GetPlayerInfo(PlayerInfo);
		if (PlayerInfo.nCurHP <= 0)
		{
			return;
		}
		
		PlayerPawn = Pawn(GetPlayerActor());
		if (PlayerPawn.CurRideType != 0)
		{
			
			if (PlayerPawn.RidePawn.HungerEmitter.Name != '')
			{
				wnd_RadarMap.SetTimer(DELAY_HIGHLIGHT_PLAYER_TIMER_ID, 100);					// little delay to fix GetRenderBoundingSphere() when pawn changed to transformed state
		//		HighlightMyPawn();
			}
		}
		else
		{
			if (PlayerPawn.HungerEmitter.Name == '')
			{
				wnd_RadarMap.SetTimer(DELAY_HIGHLIGHT_PLAYER_TIMER_ID, 100);					// little delay to fix GetRenderBoundingSphere() when pawn changed to transformed state
		///		HighlightMyPawn();
			}
		}
	}
}



function InitDatas()
{
	local int i;

	// 비행정 관련 초기화
	isOnFSTimer = false;
	wnd_RadarMap.KillTimer( FS_TIMER_ID );
	
	txt_boxMove.SetText("");
	
	inParty = false;
	
	partyCount = 0;
	partyPetCount = 0;
//	farestDistance = -1;
//	farestIndex = -1;
	
	for(i = 0; i < MAX_MONSTER; i++)
	{
		radarObject[i].ID = -1;
	}
	
	for(i = 0; i < MAX_PARTY; i++)		
	{
		arr_PartyID[i] = -1;
		arr_PartyLocX[i] = -1;
	}
	
	ClearObject();
	
	wnd_RadarMap.KillTimer(TIMER_ID1);
	wnd_RadarMap.KillTimer(TIMER_ID2);
	wnd_RadarMap.KillTimer(TIMER_ID2_LARGE);

	SetRadarElements();
	
	m_TargetName = "";
	m_TargetID = -1;
	my_ID = -1;
	
	dynamicCount = 0;
	
	questID = -1;
	questLevel = -1;
	
	isVisibleTarget = false;

	txt_noMap.HideWindow();	// "표시 불가 지역" 텍스트는 기본적으로 가려진다. 
	txt_boxMove.SetText("");			//존 표시를 초기화해줌
	
	ZoneTooltipArea = GetSystemString(1284);
	ZoneTooltipColor.R = 220;
	ZoneTooltipColor.G = 220;
	ZoneTooltipColor.B = 220;
	tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd..RadarMapWnd.tex_grey_zone");
}


//// update QuestTarget from QuestTreeWnd
function HandleMinimapShowQuest(int ID, int Level)	
{
	questID = ID;
	questLevel = Level;
}

//// update QuestTarget from QuestTreeWnd
function HandleMinimapHideQuest()
{
	if (questID > 0)
		rdr_RadarMapObject.DeleteObject(QUEST_TARGET_RADAR_ID);
	
	questID = -1;
	questLevel = -1;
}


function ClearObject()
{
	local int  i;
	
//	farestDistance = -1;
//	farestIndex = -1;
	
	for (i = 0; i < MAX_MONSTER; i++)
	{
		if(radarObject[i].ID != -1)
		{
			rdr_RadarMapObject.DeleteObject(radarObject[i].ID);
			radarObject[i].ID = -1;
			radarObject[i].iconType = "";
		}	
	}
	
	for (i = 0; i < MAX_PARTY; i++)
	{
		if (arr_PartyID[i] != -1)
		{
			rdr_RadarMapObject.DeleteObject( arr_PartyID[i]);
		}		
	}
	
	if (questID > 0)
		rdr_RadarMapObject.DeleteObject(QUEST_TARGET_RADAR_ID);

}


function ClearTarget()
{
	m_TargetID = -1;
	isVisibleTarget = false;	
	HandleTargetUpdate();
}


function StopLocal()
{	
	wnd_RadarMap.KillTimer(TIMER_ID2);
	wnd_RadarMap.KillTimer(TIMER_ID2_LARGE);
	tex_LocalMap.SetWindowSize(LocalMapRect.nWidth, LocalMapRect.nHeight);		// when doing yeleport from map with Large flag, window size can reduce to 0,0;
}


function HandleTargetUpdate()
{
	local int TargetID;
		
	TargetID = class'UIDATA_TARGET'.static.GetTargetID();
	
	if (TargetID < 1 || TargetID == my_ID)
	{
		if (m_TargetID != -1) rdr_RadarMapObject.DeleteObject(TARGET_RADAR_ID); 
		m_TargetName = "";
		m_TargetID = -1;
		m_TargetPosition.X = -1;
		m_TargetPosition.Y = -1;
		m_TargetPosition.Z = -1;
		return;
	}	
	
	if (m_TargetID == TargetID)	// same target
	{
		return;
	}
	else
	{
		rdr_RadarMapObject.DeleteObject(TARGET_RADAR_ID);
	}
			
	m_TargetID = TargetID;	
	isVisibleTarget = false;
}


//////// PARTY STUFF ////////////////

// 미니맵 데이터가 가지고 있는 모든 파티원 정보를 저장한다. 
function GetPartyLocation()
{
	local int i;
	local Vector PartyMemberLocation;
	
	for(i=0 ; i<MAX_PARTY ; i++)
	{
		if(arr_PartyID[i] != -1)
		{
			if(GetPartyMemberLocationWithID( arr_PartyID[i], PartyMemberLocation ))	//값이 있을 경우에만
			{
				if(arr_PartyLocX[i] != -1)	// -1은 값이 없다는 뜻이져~
				{
					if (PartyMemberLocation.x != -1)
						arr_PartyLocX[i] = PartyMemberLocation.x; // jdem poka priedut normlanie koordinati
					arr_PartyLocY[i] = PartyMemberLocation.y;
					arr_PartyLocZ[i] = PartyMemberLocation.z;
				}
			}
		}			
	}
}


function HandlePartyAddParty(String param )
{
	local int ID;
	local int SummonID;
	local int MasterID;
	local int idx;
	local string Name;	
	
	inParty = true;

	// 필요한 데이터를 파싱한다. 
	ParseInt(param, "ID", ID);
	ParseInt(param, "SummonID", SummonID);
	ParseString(Param, "Name", Name);
	
	if (ParseInt(param, "MasterID", MasterID))
	{
		if (MasterID > 0 && MasterID == ID)
		{	
			partyLeaderID = ID;
		}
	}
	

	//파티원 추가시 배열에 ID를 저장한다. 
	if( FindPartyIDX(ID) > -1)	// 이미 배열에 ID가 있으면 문제가 있는것
	{
		//debug("ERROR PartyID : " $ FindPartyIDX(ID) );
		return;
	}
	else
	{
		idx = findEmptyPartySlot();
		if(idx == -1)	//빈 파티원 슬롯이 없어도 문제
		{
			//debug("ERROR - No Empty Slot!! ");
			return;
		}
		else	// 파티원 서버 ID를 배열에 저장한다. 
		{
			if  (IsJustCreatedParty())
			{
				HighlightMyPawn(true);		// highlight me if party just created
				
				HighLightMyPetPawn();	// instant highlight my pet on first invite/create party
			}

			if (highlightMode)	// hihglight party members
				if (highlightPartyLeader || highlightPartyLeader || highlightUnionLeader)	// hihglight party members
					HighlightPawnInstant(ID, , true);
			
			//debug(" idx : " $ idx $ " ID : " $ ID);
			arr_PartyID[idx] = ID;
			arr_PartyLocX[idx] = 1; // chtobi srazy shel detect po GetPartyMemberLocationWithID, pust i s zadejkoi
			arr_PartyName[idx] = Name;
			partyCount++;
			
			if (SummonID > 0)
			{
				arr_PartyPetID[idx] = SummonID;
				partyPetCount++;
				if (highlightMode)
					if (highlightPartyPet)		// higlight partymembers pets
						HighlightPawnInstant(SummonID, true, true);
			}
		}
	}
}


function bool IsJustCreatedParty()
{
	local int idx;
	
	for (idx = 0; idx < MAX_PARTY; idx++)
	{
		if (arr_PartyID[idx] != -1)
			return	false;
	}	
	return true;
}



function int findEmptyPartySlot()
{
	local int  i;
	for(i=0 ; i<MAX_PARTY ; i++)
	{
		if(arr_PartyID[i] == -1)
		{
			return i;
		}			
	}	
	return -1;
}


//ID로 배열의 인덱스를 구한다. 
function int FindPartyIDX(int ID)
{
	local int idx;
	
	for (idx = 0; idx < MAX_PARTY; idx++)
	{
		if (arr_PartyID[idx] == ID)
		{
			return idx;
		}
	}
	return -1;
}


function HandlePartyDeleteParty(String param )
{
	local int ID;
	local int idx;
	
	ParseInt(param, "ID", ID);

	//특정 파티원의 삭제
	idx = FindPartyIDX(ID);

	if( idx > -1)	
	{
		StillInParty();
		
		if (inParty)
		{
			CleanPawnHighlight(ID);
		}
		else
		{
			partyLeaderID = my_ID;
			unionLeaderID = -1;
			unionLeaderName = "";
		}
		
		if (arr_PartyPetID[idx] != -1)
		{
			partyPetCount--;
			if (highlightPartyPet)
				CleanPawnHighlight(arr_PartyPetID[idx], true);
		}
		
		arr_PartyPetID[idx] = -1;
		
		rdr_RadarMapObject.DeleteObject( arr_PartyID[idx]);
		arr_PartyLocX[idx]  = -1;
		arr_PartyID[idx] = -1;
	}
	else//  배열에 ID가없으면 문제가 있는것
	{
		//debug("ERROR NO PartyID : " $ ID );
		return;
	}
}


function StillInParty()
{
	local int idx;
	local int i;
	
	i = -1;
	
	for (idx = 0; idx < MAX_PARTY; idx++)
	{
		if (arr_PartyID[idx] != -1)
			i++;
	}
	
	if (i >= 1)	//in party
	{
		inParty = true;
		partyCount--;
	}
	else 	// not in party
	{
		CleanMyHighlight();
		CleanAllPartyHighlight();
		
		inParty = false;
		isFadeInStart = false;
		partyCount = 0;
		partyPetCount = 0;
	}
}


function HandlePartyDeleteAllParty()
{
	local int i;
	
	inParty = false;
	isFadeInStart = false;
	
	// 모든 파티원 정보 삭제
	partyLeaderID = my_ID;
	
	for (i = 0 ; i < MAX_PARTY ; i++)
	{
		rdr_RadarMapObject.DeleteObject( arr_PartyID[i]);
		arr_PartyLocX[i]  = -1;
		arr_PartyID[i] = -1;
		arr_PartyPetID[i] = -1;
	}
	
	partyCount = 0;
	partyPetCount = 0;
}


function CleanMyHighlight()
{
	local Pawn PlayerPawn;
	
	if (inParty)
	{
		PlayerPawn = Pawn(GetPlayerActor());
		if (PlayerPawn.HungerEmitter.Name != '')
			PlayerPawn.RemoveHungerEffect();
	}
}


function HighlightMyPawn(optional bool isFadeIn)
{
	local Pawn PlayerPawn;
	
	if (inParty && highlightMe && highlightMode)
	{
		PlayerPawn = Pawn(GetPlayerActor());
		HighlightPawn(PlayerPawn, isFadeIn);
	}
}	


function CleanAllPartyHighlight()
{
	local int i;
	local int countParty;
	local int countPartyPet;
	
	local Actor PlayerActor;
	local Pawn AroundPawn;
	
	PlayerActor = GetPlayerActor();
	
	if (inParty == true && highlightMode)
	{
		countParty = 0;
		countPartyPet = 0;
		
		foreach PlayerActor.CollidingActors(class 'Pawn', AroundPawn, 4000, PlayerActor.Location)
		{
			if (AroundPawn.CreatureID > 0)
			{
				if (countParty < MAX_PARTY)
				{
					for (i = 0; i < MAX_PARTY; i++)
					{
						if (AroundPawn.CreatureID == arr_PartyID[i])
						{
							if (AroundPawn.CurRideType != 0 && AroundPawn.RidePawn.Name == '') 	// is rider so skip
								continue;
								
							countParty++;
							AroundPawn.RemoveHungerEffect();
							continue;
						}
					}
				}
				
				if (countPartyPet < MAX_PARTY)
				{
					for (i = 0; i < MAX_PARTY; i++)
					{
						if (AroundPawn.CreatureID == arr_PartyPetID[i])
						{
							AroundPawn.NHeroEffect.Kill();
							AroundPawn.NHeroEffect.NDestroy();
							AroundPawn.bIsHero = false;
							countPartyPet++;
							continue;
						}
					}
				}			
				
				if (AroundPawn.CreatureID == class'UIDATA_PET'.static.GetPetID())
				{
					AroundPawn.NHeroEffect.Kill();
					AroundPawn.bIsHero = false;
				}
			}
		}
	}
}


function TurnPartyHighlight(bool newHighlightMode)		// if call this from partwnd already inParty = true
{
	if (!newHighlightMode)
	{
		CleanMyHighlight();
		CleanAllPartyHighlight();	// clean all party + pets + my pet
		
		if (highlightUnionLeader)
			if (unionLeaderID != -1)
				CleanPawnHighlight(unionLeaderID);
		
		highlightMode = newHighlightMode;
	}
	else
	{
		highlightMode = newHighlightMode;
		
		HighlightMyPawn(true);
		HighLightMyPetPawn();
		
		HighlightAllParty();
	}
}


function HighLightMyPetPawn()
{
	if (highlightPartyPet)
		if (highlightPet)
			if (class'UIDATA_PET'.static.GetPetID() > 0)
				HighlightPawnInstant(class'UIDATA_PET'.static.GetPetID(), true, true);	// instant highlight my pet	
}


function ReloadHighLight(int highlightType, int highlightEffect)
{
	local string tmpName;

	if (highlightEffect > 0)
	{
		GetINIString(string(highlightEffect - 1), "EffectName", tmpName, "HighlightSettings");
	}
	
	switch(highlightType)
	{
		case 1:		// partyMember
			if (highlightEffect > 0)
			{
				highlightPartyMember = true;
				HIGHLIGHT_PARTY_MEMBER_EFFECT = Right(tmpName, Len(tmpName) - InStr(tmpName, ".") - 1);
				PartyMemberHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpName, class'Class'));
			}
			else
			{
				highlightPartyMember = false;
			}
			break;
			
		case 2:		// partyLeader
			if (highlightEffect > 0)
			{
				highlightPartyLeader = true;
				HIGHLIGHT_PARTY_LEADER_EFFECT = Right(tmpName, Len(tmpName) - InStr(tmpName, ".") - 1);
				PartyLeaderHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpName, class'Class'));
			}				
			else
			{
				highlightPartyLeader = false;
			}
			break;
			
		case 3:		// unionLeader
			if (highlightEffect > 0)
			{
				highlightUnionLeader = true;
				HIGHLIGHT_UNION_LEADER_EFFECT = Right(tmpName, Len(tmpName) - InStr(tmpName, ".") - 1);
				UnionLeaderHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpName, class'Class'));
			}
			else
			{
				highlightUnionLeader = false;
			}
			break;
			
		case 4:		// party pets
			if (highlightEffect > 0)
			{
				highlightPartyPet = true;
				HIGHLIGHT_PARTY_PET_EFFECT = Right(tmpName, Len(tmpName) - InStr(tmpName, ".") - 1);
				PartyPetHLEmitterClass = class<Emitter>(DynamicLoadObject(tmpName, class'Class'));
			}
			else
			{
				highlightPartyPet = false;
			}
			break;
	}
	
	
	if (inParty && highlightMode)
	{
		CleanMyHighlight();
		HighlightMyPawn(true);

		CleanAllPartyHighlight();
		
		if (unionLeaderID != -1)
		{
			CleanPawnHighlight(unionLeaderID);
		}
		
		HighlightAllParty();

		HighLightMyPetPawn();
	}
}


function HighlightAllParty()
{
	local Actor	PlayerActor;
	local Pawn	AroundPawn;
	
	if (inParty)
	{
		PlayerActor = GetPlayerActor();
		
		if (unionLeaderID > 0)
		{
			if (unionLeaderID == my_ID)
			{
				haveUnionLeader = true;
			}
			else
			{
				haveUnionLeader = false;
			}
		}
		else
			haveUnionLeader = true;
		
		membersCount = 0;
		membersPetCount = 0;
		
		foreach PlayerActor.CollidingActors(class 'Pawn', AroundPawn, 4000, PlayerActor.Location)
		{
			ProcessPartyPawns(AroundPawn, true);
		}
	}
}


function HighlightPawnInstant(int PawnID, optional bool isPet, optional bool isFadeIn)
{
	local Actor	PlayerActor;
	local Pawn	AroundPawn;
	
	if (inParty)
	{
		PlayerActor = GetPlayerActor();
		foreach PlayerActor.CollidingActors(class 'Pawn', AroundPawn, 4000, PlayerActor.Location)
		{
			if (AroundPawn.CreatureID == PawnID)
			{
				if (!isPet)
				{
					if (AroundPawn.CurRideType != 0  &&  AroundPawn.RidePawn.Name == '')
					{
						continue;
					}
		
					HighlightPawn(AroundPawn, isFadeIn);
					return;
				}
				else
				{
					HighlightPetPawn(AroundPawn, isFadeIn);
				}
			}
		}
	}
}


function HighlightPawn(Pawn P, optional bool isFadeIn)
{
	local UserInfo UserInfo;
	local int hlType;
	local vector spawnLocation;
	local float temp;
	local plane planePawn;

	hlType = GetHighlightType(P.CreatureID);	
	
	if (hlType == 0) 
		return;					// no need highlight

	if (P.HungerEmitter.Name != '')
	{
		P.RemoveHungerEffect();
	}
	
	if (P.CurRideType != 0)
	{
		if (P.RidePawn.HungerEmitter.Name != '')
		{
			P.RidePawn.RemoveHungerEffect();
		}
	}

	GetUserInfo(P.CreatureID, UserInfo);	/// nTransformID;		// when transformed or on mount
											///	m_bPawnChanged 		// when transformed
	P.AddHungerEffect();
	P.HungerEmitter.Kill();
	
	switch (hlType)
	{
		case 1:
			P.HungerEmitter =  P.Spawn(PartyMemberHLEmitterClass, P, , , defaultRotion);
			break;
			
		case 2:
			P.HungerEmitter =  P.Spawn(PartyLeaderHLEmitterClass, P, , , defaultRotion);
			break;

		case 3:
			P.HungerEmitter =  P.Spawn(UnionLeaderHLEmitterClass, P, , , defaultRotion);
			break;
	}
	
	if (isFadeIn)		// test fade-in highlight
	{
		if (!isFadeInStart)
		{
			isFadeInStart = true;
			wnd_RadarMap.SetTimer(FADEIN_HIGHLIGHT_TIMER_ID, 450); //test fade in mode
		}
		
		P.HungerEmitter.Tag = '0'; // flag to detect chenged emitter  in RestoreHighlightFade()
		
		for (hlType = 0; hlType <  P.HungerEmitter.Emitters.Length; hlType ++)
		{
			if (P.HungerEmitter.Emitters[hlType].FadeIn == false)
			{	
				P.HungerEmitter.Emitters[hlType].FadeIn = true;
				if (P.HungerEmitter.Emitters[hlType].FadeInEndTime < 0.5)
				{
					if (P.HungerEmitter.Emitters[hlType].LifetimeRange.Min < 0.5)
					{
						P.HungerEmitter.Emitters[hlType].FadeInEndTime = P.HungerEmitter.Emitters[hlType].LifetimeRange.Min;
					}
					else
					{
						P.HungerEmitter.Emitters[hlType].FadeInEndTime = 0.5;
					}
				}
			}
			else
			{
				if (P.HungerEmitter.Emitters[hlType].FadeInEndTime < 0.5)
				{
					P.HungerEmitter.Emitters[hlType].FadeInEndTime = 0.4;
				}
			}
		}
	}

	spawnLocation.X = 0;
	spawnLocation.Y = 0;
	spawnLocation.Z = - P.CollisionHeight;
	
	
	if (highlightScale)
	{
		if (UserInfo.nTransformID > 0 || P.CurRideType != RD_NONE)
		{
			planePawn = P.GetRenderBoundingSphere();
			temp = 0.5 * (planePawn.W/P.HungerEmitter.CollisionRadius);
		}
		else
		{
			temp = 2 * (P.CollisionRadius/P.HungerEmitter.CollisionRadius);
		}
		
		P.HungerEmitter.SetSizeScale(temp);
	}
	
//	if (P.CurRideType == 6) // mechanical bug
	if (temp > 1)
	{
		spawnLocation.Z = spawnLocation.Z - 1.25 * temp;	
	}

//	P.HungerEmitter.SetBase(P);
//	P.DetachFromBone(P.HungerEmitter);
//	P.AttachToBoneWithIndex(P.HungerEmitter, 21,  1);
	
	P.HungerEmitter.SetRelativeRotation(defaultRotion);

	P.HungerEmitter.bSelfRotation = true;
	P.HungerEmitter.bRelativeTrail = true;
	P.HungerEmitter.SetPhysics(PHYS_Trailer);
	P.HungerEmitter.RelativeTrailOffset = spawnLocation;
}


function RestoreHighlightFade()
{
	local int i;
	local int j;
	local int my_petID;
	
	local int count;
	local int petCount;
	
	local Actor PlayerActor;
	local Pawn P;
	
	local Emitter PartyMemberEmitter;
	local Emitter PartyLeaderEmitter;
	local Emitter UnionLeaderEmitter;
	local Emitter PartyPetEmitter;
	
	PartyMemberEmitter = new PartyMemberHLEmitterClass;
	PartyLeaderEmitter = new PartyLeaderHLEmitterClass;
	UnionLeaderEmitter = new UnionLeaderHLEmitterClass;
	PartyPetEmitter = new PartyPetHLEmitterClass;
	
	PlayerActor = GetPlayerActor();
	
	count = 0;
	petCount = 0;
	
	my_PetID = class'UIDATA_PET'.static.GetPetID();
	
	foreach PlayerActor.CollidingActors(class 'Pawn', P, 4000, PlayerActor.Location)
	{
		if (P.CreatureID <= 0)
			continue;
		
		if (count < partyCount)
		{
			for (i = 0; i < MAX_PARTY; i++)
			{
				if (P.CreatureID == arr_PartyID[i])
				{
					if (P.CurRideType != 0  &&  P.RidePawn.Name == '')	// if pawn is rider (skip cuz have same Id with mount)
					{
						continue;
					}
				
					count++;		// if party member have ride status -> we have 2 same CreatureID, so we skip real actor and wait for mount
		
					if (highlightUnionLeader || highlightPartyLeader || highlightPartyMember)	// private flags
					{
						if (P.HungerEmitter.Tag == '0')
						{
							P.HungerEmitter.Tag = '1';
							switch (string(P.HungerEmitter.Name))
							{
								case HIGHLIGHT_UNION_LEADER_EFFECT:
									for (j = 0; j <  P.HungerEmitter.Emitters.Length; j ++)
									{
										P.HungerEmitter.Emitters[j].FadeIn = UnionLeaderEmitter.Emitters[j].FadeIn;
										P.HungerEmitter.Emitters[j].FadeInEndTime = UnionLeaderEmitter.Emitters[j].FadeInEndTime;
									}
									break;
			
								case HIGHLIGHT_PARTY_LEADER_EFFECT:
									for (j = 0; j <  P.HungerEmitter.Emitters.Length; j ++)
									{
										P.HungerEmitter.Emitters[j].FadeIn = PartyLeaderEmitter.Emitters[j].FadeIn;
										P.HungerEmitter.Emitters[j].FadeInEndTime = PartyLeaderEmitter.Emitters[j].FadeInEndTime;
									}
									break;
											
								case HIGHLIGHT_PARTY_MEMBER_EFFECT:
									for (j = 0; j <  P.HungerEmitter.Emitters.Length; j ++)
									{
										P.HungerEmitter.Emitters[j].FadeIn = PartyMemberEmitter.Emitters[j].FadeIn;
										P.HungerEmitter.Emitters[j].FadeInEndTime = PartyMemberEmitter.Emitters[j].FadeInEndTime;
									}
									break;
							}
						}
					}
					continue;
				}
			}
		}

		if (highlightPartyPet && partyPetCount > 0)
		{
			if (petCount < partyPetCount)
			{
				for (i = 0; i < MAX_PARTY; i++)
				{
					if (P.CreatureID == arr_PartyPetID[i])
					{
						if (P.NHeroEffect.Tag == '0')
						{
							P.NHeroEffect.Tag = '1';
							for (j = 0; j <  P.NHeroEffect.Emitters.Length; j ++)
							{
								P.NHeroEffect.Emitters[j].FadeIn = PartyPetEmitter.Emitters[j].FadeIn;
								P.NHeroEffect.Emitters[j].FadeInEndTime = PartyPetEmitter.Emitters[j].FadeInEndTime;
							}
						}
						petCount++;
						continue;
					}
				}
			}
		}

		if (P.CreatureID == my_PetID)
		{
			if (highlightPartyPet && highlightPet)
			{
				if (P.NHeroEffect.Tag == '0')
				{
					P.NHeroEffect.Tag = '1';
					for (i = 0; i <  P.NHeroEffect.Emitters.Length; i ++)
					{
						P.NHeroEffect.Emitters[i].FadeIn = PartyPetEmitter.Emitters[i].FadeIn;
						P.NHeroEffect.Emitters[i].FadeInEndTime = PartyPetEmitter.Emitters[i].FadeInEndTime;
					}
				}	
			}
			continue;		
		}

		if (P.CreatureID == my_ID)
		{
			if (HighlightMe)
			{
				if (P.CurRideType != 0)
				{
					if (P.RidePawn.Name == '')			// if pawn is rider (skip cuz have same Id with mount)
					{
						continue;
					}
				}
				
				if (P.HungerEmitter.Tag == '0')
				{
					P.HungerEmitter.Tag = '1';
					switch (string(P.HungerEmitter.Name))
					{
						case HIGHLIGHT_UNION_LEADER_EFFECT:
							for (i = 0; i <  P.HungerEmitter.Emitters.Length; i ++)
							{
								P.HungerEmitter.Emitters[i].FadeIn = UnionLeaderEmitter.Emitters[i].FadeIn;
								P.HungerEmitter.Emitters[i].FadeInEndTime = UnionLeaderEmitter.Emitters[i].FadeInEndTime;
							}
							break;
									
						case HIGHLIGHT_PARTY_LEADER_EFFECT:
							for (i = 0; i <  P.HungerEmitter.Emitters.Length; i ++)
							{
								P.HungerEmitter.Emitters[i].FadeIn = PartyLeaderEmitter.Emitters[i].FadeIn;
								P.HungerEmitter.Emitters[i].FadeInEndTime = PartyLeaderEmitter.Emitters[i].FadeInEndTime;
							}
							break;
											
						case HIGHLIGHT_PARTY_MEMBER_EFFECT:
							for (i = 0; i <  P.HungerEmitter.Emitters.Length; i ++)
							{
								P.HungerEmitter.Emitters[i].FadeIn = PartyMemberEmitter.Emitters[i].FadeIn;
								P.HungerEmitter.Emitters[i].FadeInEndTime = PartyMemberEmitter.Emitters[i].FadeInEndTime;
							}
							break;
					}
				}
			}				
			continue;
		}
	
		if (P.CreatureID == unionLeaderID)
		{
			if (highlightUnionLeader && !haveUnionLeader)
			{
				if (P.CurRideType != 0)
				{
					if (P.RidePawn.Name == '')			// if pawn is rider (skip cuz have same Id with mount)
					{
						continue;
					}
				}
				
				if (P.HungerEmitter.Tag == '0')
				{
					P.HungerEmitter.Tag = '1';
					
					for (i = 0; i <  P.HungerEmitter.Emitters.Length; i ++)
					{
						P.HungerEmitter.Emitters[i].FadeIn = UnionLeaderEmitter.Emitters[i].FadeIn;
						P.HungerEmitter.Emitters[i].FadeInEndTime = UnionLeaderEmitter.Emitters[i].FadeInEndTime;
					}
				}				
			}
		}
	}
}


function CleanPawnHighlight(int PawnID, optional bool isPet) // type - 0 common, 1 pm, 2 pl, 3 ul, 4 pets 5 my pet
{
	local Actor PlayerActor;
	local Pawn AroundPawn;
	
	if (inParty && highlightMode)
	{	
		if (isPet)
		{
			if (!highlightPet && !highlightPartyPet)
				return;
		}
		else
		{
			if (!highlightUnionLeader && !highlightPartyLeader && !highlightPartyMember)
				return;
		}
		
		PlayerActor = GetPlayerActor();
		foreach PlayerActor.CollidingActors(class 'Pawn', AroundPawn, 4000, PlayerActor.Location)
		{
			if (AroundPawn.CreatureID == PawnID)
			{
				if (!isPet)
				{
					if (AroundPawn.CurRideType != 0 && AroundPawn.RidePawn.Name == '')	// is rider so skip
					{
						continue;
					}

					AroundPawn.RemoveHungerEffect();
				}
				else
				{
					AroundPawn.NHeroEffect.Kill();
					AroundPawn.bIsHero = false;
				}
				return;
			}
		}
	}
}


function HighlightPetPawn(Pawn P, optional bool isFadeIn)
{
	local vector spawnLocation;
	local float temp;
	local int i;
	local plane planePawn;
	
	if (P.NHeroEffect.Name != '')	// to prevent over highlight when change party leader
	{
		P.NHeroEffect.NDestroy();		// ?? stable ??
		P.bIsHero = false;
	}
	
	P.bIsHero = true;
	P.NHeroEffect.Kill();
	P.NHeroEffect = P.Spawn(PartyPetHLEmitterClass, P, , P.Location, defaultRotion);


	if (isFadeIn)		//test fade in mode
	{
		if (!isFadeInStart)
		{
			isFadeInStart = true;
			wnd_RadarMap.SetTimer(FADEIN_HIGHLIGHT_TIMER_ID, 450);		//test fade in mode
		}
		
		P.NHeroEffect.Tag = '0'; // flag to detect chenged emitter  in RestoreHighlightFade()
		
		for (i = 0; i <  P.NHeroEffect.Emitters.Length; i ++)
		{
			if (P.NHeroEffect.Emitters[i].FadeIn == false)
			{	
				P.NHeroEffect.Emitters[i].FadeIn = true;

				if (P.NHeroEffect.Emitters[i].FadeInEndTime < 0.5)
				{
					if (P.NHeroEffect.Emitters[i].LifetimeRange.Min < 0.5)
					{
						P.NHeroEffect.Emitters[i].FadeInEndTime = P.NHeroEffect.Emitters[i].LifetimeRange.Min;
					}
					else
					{
						P.NHeroEffect.Emitters[i].FadeInEndTime = 0.5;
					}
				}
			}
			else
			{
				if (P.NHeroEffect.Emitters[i].FadeInEndTime < 0.5)
				{
					P.NHeroEffect.Emitters[i].FadeInEndTime = 0.4;
				}
			}
		}
	}

	spawnLocation.X = 0;
	spawnLocation.Y = 0;
	spawnLocation.Z = - P.CollisionHeight;
	
//	P.NHeroEffect.SetBase(P);
	P.NHeroEffect.SetRelativeRotation(defaultRotion);
	
	if (highlightScale)
	{
		planePawn = P.GetRenderBoundingSphere();
		temp = 0.5 * (planePawn.W/P.NHeroEffect.CollisionRadius);
	//	temp = P.CollisionRadius/P.NHeroEffect.CollisionRadius;
		P.NHeroEffect.SetSizeScale(temp);
	}
	
	P.NHeroEffect.bSelfRotation = true;
	P.NHeroEffect.bRelativeTrail = true;
	P.NHeroEffect.SetPhysics(PHYS_Trailer);
	P.NHeroEffect.RelativeTrailOffset = spawnLocation;
}


function int GetHighlightType(int ID)
{
	if (ID == unionLeaderID && highlightUnionLeader)
	{
		haveUnionLeader = true;
		return 3;
	}
	else if (ID == partyLeaderID && highlightPartyLeader )
	{
		return 2;
	}
	else  if (highlightPartyMember)
	{
		return 1;
	}
	else
	{
		return 0;
	}
}




/*function  EffectViewer(string Nomer)
{
	local Actor PlayerActor;
	local Pawn PlayerPawn;
	
	local class<Emitter> HLEmitterClass;
	local string EmitterName;
//	local EffectNames script_EffectNames;
	local vector EffectViewerEmitterLoc;
	
		local Controller C;

	local  EffectNames EffectNames;
	
	EffectNames = new class'EffectNames';
	
//	script_EffectNames = EffectNames(GetScript("EffectNames"));
	
	AddSystemMessageString(" GetPlayerActor;");
	
	PlayerActor = GetPlayerActor();
C = PlayerActor.Level.GetLocalPlayerController();
	
	EffectViewerEmitterLoc = PlayerActor.Location;
	EffectViewerEmitterLoc.X = EffectViewerEmitterLoc.X + 100;
	
//	EmitterName = script_EffectNames.GetEffectName(int(Nomer));
EmitterName = EffectNames.GetEffectName(int(Nomer));
	EmitterName = "lineageeffect." $ EmitterName;
//	EmitterName = "lineageeffect." $ "e_u080_b";
	AddSystemMessageString("EmitterName:" @ EmitterName);
	
	HLEmitterClass = class<Emitter>(DynamicLoadObject(EmitterName, class'Class'));
	
//	EffectViewerEmitter.FinishAnim();	
//	EffectViewerEmitter.Kill();
//	EffectViewerEmitter.Destroy();	
//	EffectViewerEmitter.ClearL2Game();
//	if (EffectViewerEmitter.Initialized == 1)
//	{
	//	EffectViewerEmitter.Kill();
//	EffectViewerEmitter.NDestroy();
//	EffectViewerEmitter.NDestroy();
//		AddSystemMessageString("EffectViewerEmitter.Kill()");
//	}
//	EffectViewerEmitter.bRendered = false;
	
//	EffectViewerEmitter = new HLEmitterClass;
	AddSystemMessageString("PlayerActor.Spawn;");
	EffectViewerEmitter = C.Spawn(HLEmitterClass, PlayerActor,, EffectViewerEmitterLoc, defaultRotion);
//	HLEmitterClass.Kill();
//	EffectViewerEmitter.AutoDestroy = true;	// autro destroy emitter when relog (fix criterror after re-login and spawn new emimtters) 
}
*/

function  DestroyEffectViewer()
{	
//	EffectViewerEmitter.NDestroy();
//	EffectViewerEmitter.Destroy();
//	EffectViewerEmitter.ClearL2Game();
//	EffectViewerEmitter.Reset();
}




/*function PrintActorInfo(Emitter EmitterInfo)
{
	AddSystemMessageString("Actor Owner" @ EmitterInfo.Owner.Name);
	AddSystemMessageString("Actor Base" @ EmitterInfo.Base.Name);
	
	AddSystemMessageString("bool bStatic" @ EmitterInfo.bStatic);
	
	AddSystemMessageString("bool bHasActorTarget" @ EmitterInfo.bHasActorTarget);
	
	AddSystemMessageString("bool bRelativeTrail" @ EmitterInfo.bRelativeTrail);
	
	AddSystemMessageString("name AttachmentBone" @ EmitterInfo.AttachmentBone);
	
	AddSystemMessageString("EAttachType AttachType" @ EmitterInfo.AttachType);
	
	AddSystemMessageString("name Tag" @ EmitterInfo.Tag);
	AddSystemMessageString("name Event" @ EmitterInfo.Event);
	AddSystemMessageString("name L2MoveEvent" @ EmitterInfo.L2MoveEvent);
	
	AddSystemMessageString("name InitialState" @ EmitterInfo.InitialState);
	AddSystemMessageString("name Group" @ EmitterInfo.Group);
	
	AddSystemMessageString("name AttachTag" @ EmitterInfo.AttachTag);
	AddSystemMessageString("Length array<Actor> Attached" @ EmitterInfo.Attached.Length);
	AddSystemMessageString("bool bHardAttach" @ EmitterInfo.bHardAttach);


}

function PrintEmitterInfo(Emitter EmitterInfo)
{
	AddSystemMessageString("Length array<ParticleEmitter> Emitters" @ EmitterInfo.Emitters.Length);
	AddSystemMessageString("Length array<ParticleEmitter> ExtraTickEmitters" @ EmitterInfo.ExtraTickEmitters.Length);
	AddSystemMessageString("rangevector GlobalOffsetRange" @ EmitterInfo.GlobalOffsetRange.Z.Max);
	AddSystemMessageString("range TimeTillResetRange"@ EmitterInfo.TimeTillResetRange.Max);
	AddSystemMessageString("bool AutoDestroy" @ EmitterInfo.AutoDestroy);
	AddSystemMessageString("bool AutoReset" @ EmitterInfo.AutoReset);
	AddSystemMessageString("bool DisableFogging" @ EmitterInfo.DisableFogging);
	AddSystemMessageString("bool AutoReplay" @ EmitterInfo.AutoReplay);
	AddSystemMessageString("bool bRotEmitter" @ EmitterInfo.bRotEmitter);
	AddSystemMessageString("bool FixedBoundingBox" @ EmitterInfo.FixedBoundingBox);
	
	AddSystemMessageString("rotator RotPerSecond" @ EmitterInfo.RotPerSecond.YAw);
	AddSystemMessageString("FLOAT FixedBoundingBoxExpand" @ EmitterInfo.FixedBoundingBoxExpand);
	AddSystemMessageString("float SpeedRate" @ EmitterInfo.SpeedRate);
	AddSystemMessageString("Length array<sound> SpawnSound" @ EmitterInfo.SpawnSound.Length);
	AddSystemMessageString("float SoundRadius" @ EmitterInfo.SoundRadius);
	AddSystemMessageString("float SoundVolume" @ EmitterInfo.SoundVolume);
	AddSystemMessageString("float SoundPitchMin" @ EmitterInfo.SoundPitchMin);
	AddSystemMessageString("float SoundPitchMax" @ EmitterInfo.SoundPitchMax);
	AddSystemMessageString("bool SoundLooping" @ EmitterInfo.SoundLooping);
	AddSystemMessageString("float SoundLooping" @ EmitterInfo.SoundFadeInDuration);
	AddSystemMessageString("float SoundLooping" @ EmitterInfo.SoundFadeOutStart);
	AddSystemMessageString("float SoundLooping" @ EmitterInfo.SoundFadeOutDuration);
	
	AddSystemMessageString("bool ActorForcesEnabled" @ EmitterInfo.ActorForcesEnabled);
	AddSystemMessageString("bool UseParticleProjectors" @ EmitterInfo.UseParticleProjectors);
	AddSystemMessageString("bool DeleteParticleEmitters" @ EmitterInfo.DeleteParticleEmitters);
//	AddSystemMessageString("bool bAwaked" @ EmitterInfo.bAwaked);
	AddSystemMessageString("bool bRendered" @ EmitterInfo.bRendered);
	AddSystemMessageString("int Initialized" @ EmitterInfo.Initialized);
	AddSystemMessageString("box BoundingBox" @ EmitterInfo.BoundingBox.IsValid); // A bounding box.
	AddSystemMessageString("float EmitterRadius" @ EmitterInfo.EmitterRadius);	
	AddSystemMessageString("float EmitterHeight" @ EmitterInfo.EmitterHeight);
	AddSystemMessageString("vector GlobalOffset" @ EmitterInfo.GlobalOffset.X);	
	AddSystemMessageString("float TimeTillReset" @ EmitterInfo.TimeTillReset);	
	
//	AddSystemMessageString("float AccDeltatime" @ EmitterInfo.AccDeltatime);
	AddSystemMessageString("float FixedLifeTime" @ EmitterInfo.FixedLifeTime);
	AddSystemMessageString("vector TrailerPrePivot" @ EmitterInfo.TrailerPrePivot.X);	
	AddSystemMessageString("bool FirstSpawnParticle" @ EmitterInfo.FirstSpawnParticle);
	//emitter light
	AddSystemMessageString("bool bUseLight" @ EmitterInfo.bUseLight);
	AddSystemMessageString("byte LightType" @ EmitterInfo.LightType);	
	AddSystemMessageString("byte LightEffect" @ EmitterInfo.LightEffect);
	// nonblock
	// render if the distance is within
	AddSystemMessageString("range VisibleLimit" @ EmitterInfo.VisibleLimit.Max);
	AddSystemMessageString("float VisibilityInterpRange" @ EmitterInfo.VisibilityInterpRange);
	
	AddSystemMessageString("bool bSetSizeScale" @ EmitterInfo.bSetSizeScale);
	
	AddSystemMessageString("bool bSetMatrix" @ EmitterInfo.bSetMatrix);
	AddSystemMessageString("vector EnchantOffset" @ EmitterInfo.EnchantOffset.X);
	AddSystemMessageString("vector EnchantScale" @ EmitterInfo.EnchantScale.X);
	AddSystemMessageString("name EnchantBone" @ EmitterInfo.EnchantBone);
	
	AddSystemMessageString("bool bIsSkillEffectEmitter" @ EmitterInfo.bIsSkillEffectEmitter);
	AddSystemMessageString("bool bUpdate" @ EmitterInfo.bUpdate);
	AddSystemMessageString("bool bAllDead" @ EmitterInfo.bAllDead);
	AddSystemMessageString("bool bAllDisabled" @ EmitterInfo.bAllDisabled);
	AddSystemMessageString("bool bActorForces" @ EmitterInfo.bActorForces);
	AddSystemMessageString("bool bOnInitialDelay" @ EmitterInfo.bOnInitialDelay);
	
	AddSystemMessageString("float m_fLifeTime" @ EmitterInfo.m_fLifeTime);
	AddSystemMessageString("float m_fCurTime" @ EmitterInfo.m_fCurTime);
	
	AddSystemMessageString("bool IsScreenEffect" @ EmitterInfo.IsScreenEffect);

	AddSystemMessageString("bool bAllDead" @ EmitterInfo.bOrthoRender);
	AddSystemMessageString("float bAllDisabled" @ EmitterInfo.fOrthoCoordX);
	AddSystemMessageString("float bActorForces" @ EmitterInfo.fOrthoCoordY);
	AddSystemMessageString("bool bOnInitialDelay" @ EmitterInfo.bOrthoVisible);
	
//	AddSystemMessageString("bool CannotUpdateSkippable" @ EmitterInfo.CannotUpdateSkippable);
///	AddSystemMessageString("int tickCycle" @ EmitterInfo.tickCycle);
//	AddSystemMessageString("int renderCycle" @ EmitterInfo.renderCycle);
//	AddSystemMessageString("vector AttachedBoneSpaceTranslation" @ EmitterInfo.AttachedBoneSpaceTranslation.X);
//	AddSystemMessageString("rotator AttachedBoneSpaceRotation" @ EmitterInfo.AttachedBoneSpaceRotation,YAw);
}
*/

function HandleOntimer()
{
	local Actor PlayerActor;
	local Actor A;
	local Pawn P;

	local int i;
	local int index;
	local int radiusScan;
	


	if (inGamingState)	
	{
		if (isCanScan && showRadar)				
		{		
			ClearObject();		
			
			index = 0;
						
			if (hideParty == false && inParty == true)	// priblijennie koordinati party
			{
				GetPartyLocation();			
			}
			
			if (fixRadar)
			{				
				radiusScan = fixedScanRange[magstep];
			}
			else
			{
				radiusScan = freeScanRange[magstep];
			}		
			
			if  ((radiusScan - dynamicCount*100) < 100)
				dynamicCount--;

			if (dynamicCount < 0)
				dynamicCount = 0;
			
			radiusScan = radiusScan - dynamicCount*100;	// menyaem radius obzora v zavisimosti ot zagrujennosti
			
			PlayerActor = GetPlayerActor();	
			
			membersCount = 0;
			membersPetCount = 0;
			
			if (unionLeaderID > 0)
			{
				if (unionLeaderID == my_ID)
				{
					haveUnionLeader = true;
				}
				else
				{
					haveUnionLeader = false;
				}
			}
			else
				haveUnionLeader = true;
			
		//	ScanActor();
			
			if (showDropHighlight && !HideDropItem)	// HideDropItem from OPtionWnd
			{
				foreach PlayerActor.CollidingActors( class 'Actor', A, radiusScan, PlayerActor.Location)
				{
					if (A.Tag == 'L2Pickup')
					{
						if (CheckDropActors(A)) 
						{
							continue;
						}
					}
					
					P = Pawn(A);
					if (GeneralActorProcess(P, index, PlayerActor.Location.Z)) 
						continue;
					else
						index++;
				}
			}
			else
			{
				foreach PlayerActor.CollidingActors( class 'Pawn',  P, radiusScan, PlayerActor.Location)
				{
					if (GeneralActorProcess(P, index, PlayerActor.Location.Z)) 
						continue;
					else
						index++;
				}
			}
				
			/// opredelyaem tipi iconok npc dlya radara
			for (i = 0; i < MAX_MONSTER; i++)
			{
				if (radarObject[i].ID > 0)
				{
					switch (radarObject[i].iconType)
					{
						case "Monster":
						case "z_quest_red_start":
						case "z_quest_blue_start":
						case "z_quest_red_progress":
						case "z_quest_blue_progress":
						case "z_quest_blue_end":
						case "z_quest_red_end":
							break;
						default:
						//	radarObject[i].iconType = CheckNpcType(radarObject[i].titleName);
							radarObject[i].iconType = CheckNpcType(radarObject[i].iconType);
							break;
					}		
				}
			}

			MyPosition = GetPlayerPosition();
			
			///	rasstavlyaem ikonki npc na radare
			AddNpcObject();

			///	rasstavlyaem ikonki party na radare
			if (hideParty == false && inParty == true)
				AddPartyObject();
				
			/// rasstavlyaem ikonku tekushego targeta na radare
			AddTargetObject();
			
			/// rasstavlyaem ikonku tekushego aktivnogo kvesta na radare
			AddQuestObject();
				
			/// opredelyaem uvelichivat' radius scana actorov ili net
			if (index >= MAX_MONSTER)
				dynamicCount++;
			else
				dynamicCount--;
		
			wnd_RadarMap.KillTimer(TIMER_ID1);
			wnd_RadarMap.SetTimer(TIMER_ID1,TIMER_DELAY1);
		}		
	}	
}


function bool CheckDropActors(Actor DropActor)
{
	local L2Pickup L2P;
	local string MeshName;	
	local int dropType;

	L2P = L2Pickup(DropActor);
		
	if (L2P.DropEffectActor.Initialized == 1)
	{
		if (L2P.DropEffectActor.Tag == 'e_u056_b')	// Tag - editable, Name - const
		{
			MeshName = DropActor.GetMeshName();
			if (MeshName == "drop_ring_m00" || MeshName == "drop_earring_m00" || MeshName == "drop_necklace_m00")
			{
				dropType = 1;
			}
			else if (InStr(MeshName, "drop") == -1)
			{
				if  (InStr(MeshName, "hield") == -1 && InStr(MeshName, "igil") == -1)		// sShield sSigil
					dropType = 2;
				else
					dropType = 3;
			}
			else if (MeshName == "drop_highlight_helmet" || (InStr(meshName, "_vamprbic_") == -1 && InStr(meshName, "_move_") == -1 && InStr(meshName, "_critical_") == -1  && InStr(meshName, "_speed_") == -1	//herbs
				&& isArmorName(MeshName)))		// if "_" >= 4
			{
				dropType = 3;
			}
			else
			{
				L2P.DropEffectActor.Tag = 'S';	//skip
				return true;
			}
			
			L2P.DropEffectActor.Kill();
			
			switch (dropType)
			{
				case 1:
					 
					L2P.DropEffectActor = DropActor.Spawn(HLEmitterClassAccessory, DropActor, , DropActor.Location, defaultRotion);
					break;
					
				case 2:
					L2P.DropEffectActor =  DropActor.Spawn(HLEmitterClassWeapon, DropActor, , DropActor.Location, defaultRotion);
					break;
					
				case 3:
					L2P.DropEffectActor = DropActor.Spawn(HLEmitterClassArmor, DropActor, , DropActor.Location, defaultRotion);
					break;
			}
			return true;
		}
		else
		{
			return true;
		}
	}
	else
	{
		return true;
	}
}


function bool GeneralActorProcess(Pawn P, int index, int LocZ)
{
	local UserInfo tempObject;
	local int i;
	
	if (P.CreatureID > 0) // && P.CreatureID != m_TargetID) // P.CreatureID != my_ID
	{	
		if (inParty == true) // utochnayem koordinaty ikonok party cheerez actori			//b.Npc - mount have npc status		if (hideParty == false || highlightMode == true) /
		{
			if (hideParty == false || highlightMode == true) 
			{
				if (ProcessPartyPawns(P))
				{
					return true;
				}
			}
		}

		if (index < MAX_MONSTER)
		{
			if (!P.bNpc) return true;	// otseivaem drugih igrokov
						
			if (abs(P.Location.Z - LocZ) > 800) return true; // otseivaem po visote Z
										
			GetUserInfo(P.CreatureID, tempObject);
						
			if ((tempObject.bCanBeAttacked && !showMonster) || tempObject.bPet) return true;	// fil'tr po pokazu monstrov i petov
		
			if (tempObject.Name == "") return true; // fil'tr po bezlikim bolvankam:)
										
			for (i = 0 ; i < MAX_MONSTER; i++)	// otseivaem povotorenie actorov npc
			{
				if (radarObject[i].ID == tempObject.nID)
				{
					return true;
				}
			}

			radarObject[index].ID = P.CreatureID;
			radarObject[index].Location = P.Location;
		//	radarObject[index].Distance = GetDistanceFromMe(P.Location.X, P.Location.Y);
			radarObject[index].isMonster = tempObject.bCanBeAttacked;
		//	radarObject[index].Name = tempObject.Name;
			radarObject[index].titleName = tempObject.strNickName;
						
			if (P.NQuestMarkEffect.Name != 'None')
			{
				radarObject[index].iconType = string(P.NQuestMarkEffect.Name);
				radarObject[index].Name = "#Name=" $ string(P.CreatureID) @ "#Length=" $ P.NQuestList.Length;
				radarObject[index].titleName = "Quest";
				for (i = 0 ; i < P.NQuestList.Length; i++)
				{
					radarObject[index].Name = radarObject[index].Name @ "#Q" $ string(i) $ "=" $ (P.NQuestList[i]) $ " ";							
				}
			}
			else 
			{
				radarObject[index].Name = "#ID=" $ string(radarObject[index].ID);
				if (radarObject[index].isMonster)
				{
					radarObject[index].iconType = "Monster";
				}
				else
					radarObject[index].iconType = string(P.NpcClassID);
			}
						
			return false;
		}
		return true;
	}	
}


function bool ProcessPartyPawns(Pawn P, optional bool isFadeIn)
{
	local int i;
	
	if (hideParty == false || highlightMode == true) // utochnayem koordinaty ikonok party cheerez actori	
	{
		if (membersCount < partyCount)
		{
			for (i = 0; i < MAX_PARTY; i++)
			{
				if (P.CreatureID == arr_PartyID[i])
				{
					if (P.CurRideType != 0  &&  P.RidePawn.Name == '')	// if pawn is rider (skip cuz have same Id with mount)
					{
						if (P.HungerEmitter.Name != '')		// remove rider highlight
						{
							P.RemoveHungerEffect();
						}
						return true;
					}
								
					membersCount++;		// if party member have ride status -> we have 2 same CreatureID, so we skip real actor and wait for mount
							
					arr_PartyLocX[i] = P.Location.X;
					arr_PartyLocY[i] = P.Location.Y;
					arr_PartyLocZ[i] = P.Location.Z;
							
					if (highlightMode)	// common turn on/off highlight flag (controls form partywnd button)
					{
						if (highlightUnionLeader || highlightPartyLeader || highlightPartyMember)	// private flags
						{
							if (P.HungerEmitter.Name == '')
							{
								HighlightPawn(P, isFadeIn);
							}
						}
					}	
					return true;
				}
			}
		}
	}
	
	if (highlightMode)
	{
		if (highlightPartyPet && partyPetCount > 0)
		{
			if (membersPetCount < partyPetCount)
			{
				for (i = 0; i < MAX_PARTY; i++)
				{
					if (P.CreatureID == arr_PartyPetID[i])
					{
						if (P.NHeroEffect.Name == '')
						{
							HighlightPetPawn(P, isFadeIn);
						}
						membersPetCount++;
						return true;
					}
				}
			}
		}
	
		if (P.CreatureID == my_ID)
		{
			if (HighlightMe)
			{
				if (P.CurRideType != 0)
				{
					if (P.RidePawn.Name == '')			// if pawn is rider (skip cuz have same Id with mount)
					{
						if (P.HungerEmitter.Name != '')
						{
							P.RemoveHungerEffect();
						}
						return true;
					}
				}
				
				if (P.HungerEmitter.Name == '')
				{
					HighlightPawn(P, isFadeIn);
				}
			}
			return true;
		}
				
		if (highlightUnionLeader && !haveUnionLeader)
		{
			if (P.CreatureID == unionLeaderID)
			{
				if (P.CurRideType != 0)
				{
					if (P.RidePawn.Name == '')			// if pawn is rider (skip cuz have same Id with mount)
					{
						if (P.HungerEmitter.Name != '')
						{
							P.RemoveHungerEffect();
						}
						return true;
					}					
				}
				
				if (P.HungerEmitter.Name == '')
				{		
					HighlightPawn(P, isFadeIn);
				}		
				haveUnionLeader = true;
				return true;
			}
		}
	}
	return false;
}


function AddQuestObject()
{
	local vector questNpcLoc;
	
	if (questID > 0)
	{		
		questNpcLoc = class'UIDATA_QUEST'.static.GetTargetLoc(questID, questLevel);	
			if (isVisibleObject(questNpcLoc.X, questNpcLoc.Y))
				rdr_RadarMapObject.AddObject(QUEST_TARGET_RADAR_ID, "QuestTarget", "#QID=" $ questID @ "#Level=" $ questLevel, questNpcLoc.X, questNpcLoc.Y, questNpcLoc.Z);
	}
}


function AddNpcObject()
{
	local int i;
	
	for (i = 0; i < MAX_MONSTER; i++)
	{
		if  (radarObject[i].ID > 0)
		{
			if (fixRadar && magStep >= 2) // na 2, 3, 4 mogut vilezt' za predeli okna
			{	
				if (isVisibleObject(radarObject[i].Location.X, radarObject[i].Location.Y))
					rdr_RadarMapObject.AddObject(radarObject[i].ID , radarObject[i].iconType, radarObject[i].Name, radarObject[i].Location.X, radarObject[i].Location.Y, radarObject[i].Location.Z);
			}
			else
			{
				rdr_RadarMapObject.AddObject(radarObject[i].ID , radarObject[i].iconType, radarObject[i].Name, radarObject[i].Location.X, radarObject[i].Location.Y, radarObject[i].Location.Z);
			}
		}
	}
}


function AddPartyObject()
{
	local int i;
	
	for (i = 0; i < MAX_PARTY; i++)
	{
		if (arr_PartyID[i] != -1)
			if (arr_PartyID[i] != m_TargetID || (arr_PartyID[i] == m_TargetID && m_TargetPosition.X == 0))
				if (isVisibleObject(arr_PartyLocX[i], arr_PartyLocY[i]))
					rdr_RadarMapObject.AddObject(arr_PartyID[i] , "PartyMember", arr_PartyName[i], arr_PartyLocX[i], arr_PartyLocY[i], arr_PartyLocZ[i]);
	}
}


function AddTargetObject()
{
	local UserInfo TargetInfo;
	
	if (m_TargetID > 0) 
	{			
		GetTargetInfo(TargetInfo);
		m_TargetName =  TargetInfo.Name;
		m_TargetPosition = TargetInfo.Loc;
		/////////////////////////////////////////////
		// mojno dobavit' proverku na uslovie chto vzyat target na chlena party vne progruzki actorov 
		// t.e. kak party on otseivaetsya usloviem sverhu, t.k. id beretsya, no koordinati ne dostupni 
		// i plus oni sbrosheni cherez handletarhetupdate v (-1,-1,-1). Sdelal cherez party ikonki
		/////////////////////////////////////////// 
		if (isVisibleObject(TargetInfo.Loc.X, TargetInfo.Loc.Y))
		{	
			if (!isVisibleTarget)
				rdr_RadarMapObject.AddObject(TARGET_RADAR_ID, "Target",  m_TargetName, m_TargetPosition.X, m_TargetPosition.Y, m_TargetPosition.Z);
			else 
				rdr_RadarMapObject.UpdateObject( TARGET_RADAR_ID , m_TargetPosition.X, m_TargetPosition.Y,  m_TargetPosition.Z);
					
				isVisibleTarget = true;
		}
		else
		{
			isVisibleTarget = false;
			rdr_RadarMapObject.DeleteObject(TARGET_RADAR_ID);
		}
				
	}
	else
	{
		rdr_RadarMapObject.DeleteObject(TARGET_RADAR_ID);
	}
}


function string CheckNpcType(string iconType)
{
	local string typeName;
/*	if (InStr(Name, "Warehouse") > -1)
	{
		typeName = "Warehouse";
	}
	else
	{
		if (Name == "Gatekeeper")
		{
			typeName = "Gatekeeper";
		}
		else
		{
			typeName = "Npc";
		}
	} */
	switch (int(iconType))
	{
		// Town Gatekeepers
		case 30006:		//talking
		case 30059:		//dion
		case 30134:		//darkelf
		case 30146:		//elf
		case 30080:		//giran
		case 30899:		//heine		
		case 30177: 	//oren
		case 30233:		//hunter
		case 30256:		//gludio
		case 30320:		//gludin
		case 30540:		//dwarf
		case 30576:		//orc
		case 30848:		//aden
		case 31275:		// gddard
		case 31320:		// runa
		case 31698:		// runa
		case 31699:		// runa
		case 31964:		//shuttgrt
		case 32163:		//kamael
		case 30995:		//mtd
	//	case 32378:		//fantasy

///////// Gatekeeper Ziggurat ////////
		case 31095:
		case 31096:
		case 31097:
		case 31098:
		case 31099:
		case 31100:
		case 31101:
		case 31102:
		case 31103:
		case 31104:
		case 31105:
		case 31106:
		case 31107:
		case 31108:
		case 31109:
		case 31110:
		case 31111:
		case 31112:
		case 31114:
		case 31115:
		case 31116:
		case 31117:
		case 31118:
		case 31119:
		case 31120:
		case 31121:
		case 31122:
		case 31123:
		case 31124:
		case 31125:
			typeName = "Gatekeeper";
			break;
			
///////// Warehouse Type //////////
		case 30005:		//talking
		case 30054:		//talking
		case 30055:		//talking
		case 32170:		//kamael
		case 32171:		//kamael
		case 32172:		//kamael
		case 30316:		//gludin
		case 30498:		//gludin
		case 30210:		//gludin
		case 30255:		//gludio
		case 30503:		//gludio
		case 30322:		//gludio
		case 30594:		//dion
		case 30058:		//dion
		case 30057:		//dion
		case 30511:		//giran
		case 30103:		//giran
		case 30104:		//giran	
		case 30083:		//giran
		case 30086:		//giran
		case 30092:		//giran	
		case 30095:		//giran	
		case 30894:		//heine
		case 30895:		//heine
		case 30896:		//heine
		case 30182:		//oren
		case 30183:		//oren	
		case 30676:		//oren			
		case 30079:		//floran
		case 31773:		//floran
		case 30843:		//aden
		case 30844:		//aden
		case 30845:		//aden
		case 30232:		//hunter
		case 30686:		//hunter
		case 30685:		//hunter
		case 30139:		//darkelf
		case 30140:		//darkelf
		case 30350:		//darkelf
		case 30151:		//elf
		case 30152:		//elf
		case 30153:		//elf
		case 30562:		//orc
		case 30563:		//orc
		case 30520:		//dwarf
		case 30521:		//dwarf
		case 30522:		//dwarf
		case 31311:		//runa
		case 31312:		//runa
		case 31313:		//runa
		case 31314:		//runa
		case 31315:		//runa
		case 31959:		//shuttgrt
		case 31956:		//shuttgrt
		case 31957:		//shuttgrt
		case 32092:		//shuttgrt
		case 31958:		//shuttgrt
		case 31270:		//gddrd
		case 31267:		//gddrd
		case 31268:		//gddrd
		case 31269:		//gddrd
		case 31225:		// mtd
		case 4315:		//fantasy
			typeName = "Warehouse";
			break;
			
		default:
			typeName = "Npc";
			break;
	}	
	return typeName;
}


// 특정 좌표와 내 위치와의 거리를 잰다.
function int GetDistanceFromMe( int x, int y)
{
	local int distance;
	local int distX;
	local int distY;

	distance = int( Sqrt ( ( x - MyPosition.x ) ^ 2 + (y - MyPosition.y) ^ 2 ));
	distX = x - MyPosition.x;
	distY = y - MyPosition.y;
	distance =  int( Sqrt (distX * distX + distY * distY ));
	return distance;
}


//옵션 관련 변환 처리 // 내위치 표시 및 레이더 고정
function SetRadarElements()
{	
	if(!showMe)	//이게false이면 내 위치를 감춰준다. 
	{
		tex_myPosition.HideWindow();
	}
	else		//true 이면 보여줌
	{
		tex_myPosition.ShowWindow();
	}
		
	if (fixRadar)	//이게true이면레이더 회전 금지 
	{
		rdr_RadarMapTex.ClearRotation();
		rdr_RadarMapObject.ClearRotation();
			
		tex_Compas.ClearRotation();

		if (showMe) 
			tex_myAngle.ShowWindow();
		else
			tex_myAngle.HideWindow();
			
		rdr_RadarMapTex.SetEnableRotation(false);
		rdr_RadarMapObject.SetEnableRotation(false);
			
		tex_LocalMap.SetAutoRotateType( ETART_None );
		tex_Compas.SetAutoRotateType( ETART_None );		//나침반 기능도 정지
		
		tex_myAngle.SetRotatingDirection(1);
		tex_myAngle.SetAutoRotateType( ETART_Camera );		
		tex_myPosition.SetTexture("MonIcaEssence.RadarMapWnd.tex_my_pawn");
		tex_myPosition.SetAutoRotateType( ETART_Pawn );	//내 표시는 카메라와 같은 방향
	
	}
	else		//false 이면 레이더 회전
	{
		rdr_RadarMapTex.SetEnableRotation(true);
		rdr_RadarMapObject.SetEnableRotation(true);
		
		tex_LocalMap.SetAutoRotateType( ETART_Camera );
		tex_Compas.SetAutoRotateType( ETART_Camera );		//나침반 기능도 회복

		tex_myAngle.HideWindow();
		tex_myAngle.ClearRotation();
		tex_myPosition.SetTexture("L2UI_CT1.Radarmap_df_ICN_PC");
		tex_myPosition.SetAutoRotateType( ETART_None );
	}
}


function bool isVisibleObject(int locX, int locY)
{
	if (fixRadar)
	{
		if ((locX > (MyPosition.X - fixedRangeDrawX[magStep])) && (locX < (MyPosition.X + fixedRangeDrawX[magStep])))
		{
			if ((locY > (MyPosition.Y - fixedRangeDrawY[magStep])) && (locY < (MyPosition.Y + fixedRangeDrawY[magStep])))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}	
	else
	{
		if (GetDistanceFromMe(locX, locY) > freeRangeDrawXY[magStep])
		{
			return false;				
		}
		else
		{
			return true;					
		}
	}
}


// 타이머 딜레이마다  위치정보를 요청한다. 
function OnTimer(int TimerID)
{
	local int deltax, deltay;	
	local int changeX, changeY;
	
	switch (TimerID)		
	{
		///////////////////////////// 	little delay to fix GetRenderBoundingSphere() when pawn changed to transformed state
		case DELAY_HIGHLIGHT_PLAYER_TIMER_ID:
			wnd_RadarMap.KillTimer(DELAY_HIGHLIGHT_PLAYER_TIMER_ID);
			HighlightMyPawn();
			break;
			
		///////////////////////////// 	restore default emittes[] fadein values
		case FADEIN_HIGHLIGHT_TIMER_ID:
			wnd_RadarMap.KillTimer(FADEIN_HIGHLIGHT_TIMER_ID);
			if (inParty && highlightMode)
				RestoreHighlightFade();
			isFadeInStart = false;
			break;
		
		/////////////// RadarMap ////////////// 	
		case TIMER_ID1:
			HandleOntimer();
			break;

		/////////////// LocalZone move map ////////////// 	
		case TIMER_ID2: 
			MyPosition = GetPlayerPosition();			

			deltax = (MyPosition.x - LocalMapX)/arrCoefficient[2];	//arrCoefficient[2] - MapScale from LocalMap.ini	(0.05; 0.03125 for classic official maps turns into 20; 32)
			deltay = (MyPosition.y- LocalMapY)/arrCoefficient[2];
					
			tex_LocalMap.SetUV(deltax - LocalMapRect.nWidth/2, deltay - LocalMapRect.nHeight/2);
			
			// zaderjka chtobi krasivo postavit karty)
			if (!show)	
			{
				tex_LocalMap.ShowWindow();
				rdr_RadarMapObject.SetMapInvisible(false);
				show = true;
			}
			break;
						
		/////////////// LocalZone move map for Wide maps ////////////// 		
		case TIMER_ID2_LARGE: 
			MyPosition = GetPlayerPosition();			

			deltax = (MyPosition.x - LocalMapX)/arrCoefficient[2];	//arrCoefficient[2] - MapScale from LocalMap.ini	(0.05; 0.03125 for classic official maps turns into 20; 32)
			deltay = (MyPosition.y- LocalMapY)/arrCoefficient[2];
			
		//	if (deltax - LocalMapRect.nWidth/2 < 0)
		//		AddSystemMessageString("NULEVAYA SHURUNA");
			
		//	if (deltay - LocalMapRect.nHeight/2 < 0)
		//		AddSystemMessageString("NULEVAYA VISOTA");

			if (deltax - LocalMapRect.nWidth/2 < 0)
			{
				changeX = (LocalMapRect.nWidth/2 - deltax) * 2;
				
				tex_LocalMap.SetWindowSize(LocalMapRect.nWidth - (LocalMapRect.nWidth/2 - deltax) * 2, LocalMapRect.nHeight);
				deltax = LocalMapRect.nWidth/2;
			}
			else
			{
				changeX = 0;
			}
			
			if (deltay - LocalMapRect.nHeight/2 < 0)
			{
				changeY = (LocalMapRect.nHeight/2 - deltay) * 2;
				deltay =  LocalMapRect.nHeight/2;
			}
			else
			{
				changeY = 0;
			}
			
			tex_LocalMap.SetWindowSize(LocalMapRect.nWidth - changeX, LocalMapRect.nHeight - changeY);
			
			tex_LocalMap.SetUV(deltax - LocalMapRect.nWidth/2, deltay - LocalMapRect.nHeight/2);
			
			if (!show)	
			{
				tex_LocalMap.ShowWindow();
				rdr_RadarMapObject.SetMapInvisible(false);
				show = true;
			}
			break;
			
		/////////////// Zone Name ////////////// 		
		case TIMER_ZONE_NAME: 
			wnd_RadarMap.KillTimer(TIMER_ZONE_NAME);
			txt_boxMove.SetAlpha(0, 0.8f);
			tex_ZoneName.SetAlpha(0, 0.8f);
			break;
			
		/////////////// No Map text ////////////// 					
		case TIMER_ID3: 
			wnd_RadarMap.KillTimer( TIMER_ID3 );
			txt_noMap.SetAlpha( 0, 0.8f );
			break;
			
		/////////////// SystemFlyTutorialBoxt ////////////// 					
		case FS_TIMER_ID: 
			if(!GetOptionBool( "Game", "SystemTutorialBox" ))	// 시스템 튜토리얼 체크박스를 확인해 주어야 한다. 
			{	
				ShowAirShipTutorial(-1);	// 시스템 메세지 랜덤 추가
			}
			else
			{
				isOnFSTimer = false;
				wnd_RadarMap.KillTimer( FS_TIMER_ID );	// 타이머를 죽여준다.
			}
			break;
			
		/////////////// No Map text ////////////// 					
		case TIMER_SHOW_RESIZE: 
			wnd_RadarMap.KillTimer(TIMER_SHOW_RESIZE);
			wnd_ResizeArea.ShowWindow();
			itm_ResizeArrow.HideWindow();
			break;
			
		/////////////// Zoom in ////////////// 					
		case TIMER_ZOOM_IN: 
			switch (magStep)
			{				
				case 1: // 
				case 2: // 0.15
				case 3: //0.1
					tex_BackAlpha.SetAlpha(arrAlpha[magStep] + (arrAlpha[magStep+1] - arrAlpha[magStep]) * (zoomStepping - currentStepping) / zoomStepping); // tekushaya alpa + delta(alphi)* n/stepping
					break;
			}
				
			rdr_RadarMapTex.SetMagnification(arrMag[magStep+1] + (arrMag[magStep] - arrMag[magStep+1]) * currentStepping / zoomStepping); // sleduishii mag + delta(mag) * n/stepping
			rdr_RadarMapObject.SetMagnification(arrMag[magStep+1] + (arrMag[magStep] - arrMag[magStep+1]) * currentStepping / zoomStepping);
			currentStepping--;
				
			wnd_RadarMap.KillTimer(TIMER_ZOOM_IN);
				
			if (currentStepping > 0)
			{
				wnd_RadarMap.SetTimer( TIMER_ZOOM_IN, TIMER_DELAY_ZOOM);
			}
			else
			{
				magStep++;
				prevmagStep = magStep;				
				mag = arrMag[magStep];
				
				rdr_RadarMapTex.SetMagnification(mag);
				rdr_RadarMapObject.SetMagnification(mag);
				
				SetRadiusTexture();
				SetSliderPos();
				
				btn_Minus.EnableWindow();
				btn_Plus.EnableWindow();
			}
			break;
			
		/////////////// Zoom out ////////////// 					
		case TIMER_ZOOM_OUT: 
			switch (magStep)
			{				
				case 2: // 0.15	
				case 3: // 0.1
				case 4: //0.085
					tex_BackAlpha.SetAlpha(arrAlpha[magStep-1] + (arrAlpha[magStep] - arrAlpha[magStep-1]) * (zoomStepping - currentStepping) / zoomStepping ); //sleduydhaya alpa + deltaalpha(tekushaya-sled)* n/stepping
					break;
			}
			
			rdr_RadarMapTex.SetMagnification(arrMag[magStep] + (arrMag[magStep-1] - arrMag[magStep])*currentStepping/zoomStepping);	// tekyashii mag + deltamag(sled-tekush) * n/stepping
			rdr_RadarMapObject.SetMagnification(arrMag[magStep] + (arrMag[magStep-1] - arrMag[magStep])*currentStepping/zoomStepping);
		
			currentStepping++;
			
			wnd_RadarMap.KillTimer(TIMER_ZOOM_OUT);
			
			if (currentStepping < zoomStepping)
			{
				wnd_RadarMap.SetTimer(TIMER_ZOOM_OUT, TIMER_DELAY_ZOOM);
			}
			else
			{
				magStep--;
				prevmagStep = magStep;					
				mag = arrMag[magStep];
				
				rdr_RadarMapTex.SetMagnification(mag);
				rdr_RadarMapObject.SetMagnification(mag);
				
				SetRadiusTexture();
				SetSliderPos();
				
				btn_Minus.EnableWindow();
				btn_Plus.EnableWindow();
			}
			break;	
	}
}


function SetRadiusTexture()
{
	/// set zoom transparency
	if (tex_LocalMap.GetTextureName() != "" && magStep == 2)	// local zone map
		tex_BackAlpha.SetAlpha(40);
	else
		tex_BackAlpha.SetAlpha(arrAlpha[magStep]);
	
	tex_rangeRadiusBig.SetWindowSize(2*freeScanRange[magStep]/arrCoefficient[magstep], 2*freeScanRange[magStep]/arrCoefficient[magstep]);
	tex_rangeRadiusSmall.SetWindowSize(2*freeScanRange[magStep]/arrCoefficient[magstep] + 2*freeScanRange[magStep]/(arrCoefficient[magstep]*3), 2*freeScanRange[magStep]/arrCoefficient[magstep] + 2*freeScanRange[magStep]/(arrCoefficient[magstep]*3));	// lodki lodki lodki
	
	if (magStep != 0) 
	{
		if (2*freeScanRange[magStep]/arrCoefficient[magstep] < 300)
			tex_rangeRadiusSmall.ShowWindow();	
		else
			tex_rangeRadiusBig.ShowWindow();
	}
	
	dynamicCount = 0;
}


function SetSliderAlpha(bool isLocal)
{
	if (isLocal)
	{
		btn_Plus.SetTexture("MonIcaEssence.RadarMapWnd.btn_plus_disable","MonIcaEssence.RadarMapWnd.btn_plus_disable","MonIcaEssence.RadarMapWnd.btn_plus_disable");
		btn_Minus.SetTexture("MonIcaEssence.RadarMapWnd.btn_minus_disable","MonIcaEssence.RadarMapWnd.btn_minus_disable","MonIcaEssence.RadarMapWnd.btn_minus_disable");
		tex_Slider.SetTexture("MonIcaEssence.RadarMapWnd.tex_slider" $ string(prevmagStep+1) $ "_disable");
	}
	else
	{
		btn_Plus.SetTexture("MonIcaEssence.RadarMapWnd.btn_plus","MonIcaEssence.RadarMapWnd.btn_plus_down","MonIcaEssence.RadarMapWnd.btn_plus_over");
		btn_Minus.SetTexture("MonIcaEssence.RadarMapWnd.btn_minus","MonIcaEssence.RadarMapWnd.btn_minus_down","MonIcaEssence.RadarMapWnd.btn_minus_over");
		SetSliderPos();
	}
}


function SetSliderPos()
{
	tex_Slider.SetTexture("MonIcaEssence.RadarMapWnd.tex_slider" $ string(magStep+1));
}


function OnClickButton( String a_ButtonID )
{
//	local ItemID testID;
//	local int i;
//	local string ioo;
//	local float idx;
//	local vector ydx;
//	local UserInfo AttackerInfo;
//	local Actor  kekActor,TestActor;
//   local Controller C;
//	Local Pawn P;
//	local L2Pickup L2P;
	
//	local Interaction NewInteraction;
//	local Emitter NewEmitter;
//	local class<Interaction> NewInteractionClass;
//	local class<Emitter> NewEmitterClass;
	
//local Emitter dastdast;
	
//	local Pickup Pup;
	
//local L2Float L2F;
//	Local Inventory Invent;	

	switch( a_ButtonID )
	{
		case "BtnPlus":
			if (magStep < MAX_MAG)
			{
				btn_Minus.DisableWindow();
				btn_Plus.DisableWindow();
				
				tex_rangeRadiusBig.HideWindow();
				tex_rangeRadiusSmall.HideWindow();

				ClearTarget();
				ClearObject();
				
				if (magStep == 0)
				{
					zoomStepping = 8;
					currentStepping = 7;
				}
				else
				{
					zoomStepping = 5;
					currentStepping = 4;
				}			
				wnd_RadarMap.SetTimer(TIMER_ZOOM_IN, 10);
			}
			break;
		case "BtnMinus":
			if (magStep > MIN_MAG)
			{
				btn_Minus.DisableWindow();
				btn_Plus.DisableWindow();
				
				tex_rangeRadiusBig.HideWindow();
				tex_rangeRadiusSmall.HideWindow();

				ClearTarget();
				ClearObject();
				
				if (magStep == 1)
					zoomStepping = 8;
				else
					zoomStepping = 5;
				
				currentStepping = 1;
				wnd_RadarMap.SetTimer(TIMER_ZOOM_OUT, 10);		
			}
			break;
		case "btnFixedOn":  
			OnClickFixedOnButton();
			break;
		case "btnFixedOff":  
			OnClickFixedOffButton();
			break;
		case "btnShowMobOn":  
			OnClickShowMobOnButton();
			break;
		case "btnShowMobOff":  
			OnClickShowMobOffButton();
			break;
		case "btnShowPartyOn":  
			OnClickShowPartyOnButton();
			break;
		case "btnShowPartyOff":  
			OnClickShowPartyOffButton();
			break;
		case "btnShowMeOn":  
			OnClickShowMeOnButton();
			break;
		case "btnShowMeOff":  
			OnClickShowMeOffButton();
			break;
		case "btnAlfaOn":  
			OnClickAlfaOnButton();
		//	ScanActor();
		/*		
		i=0;
   
		kekActor = GetPlayerActor();
		
		C = kekActor.Level.GetLocalPlayerController();
		
		foreach C.CollidingActors( class 'Actor', TestActor, 500.0, GetPlayerPosition())
		{
				i++;
				L2F = L2Float(TestActor);
				P = Pawn(TestActor);
				
				Invent = P.FindInventoryType(class 'L2Pickup');
				
				testID.ClassID = 144;
				testID.ServerID = TestActor.CreatureID;
				
				AddSystemMessageString("TestActor.CreatureID" @ string(TestActor.CreatureID));
				AddSystemMessageString("TestActor.GetMeshName" @ TestActor.GetMeshName());
				AddSystemMessageString("TestActor.Tag" @ TestActor.Tag);
		
				AddSystemMessageString("TestActor.CreatureID" @ string(TestActor.CreatureID));
				AddSystemMessageString("TestActor.Name" @ TestActor.Name);	
				AddSystemMessageString("TestActor.GetHumanReadableName" @ TestActor.GetHumanReadableName()); 
				AddSystemMessageString("TestActor.ObjectFlags" @ TestActor.ObjectFlags);
				AddSystemMessageString("TestActor.CacheIndex" @ TestActor.CacheIndex);
				AddSystemMessageString("TestActor.IndexBuffer" @ TestActor.IndexBuffer);
				AddSystemMessageString("TestActor.ActorRenderData.Ptr" @ string(TestActor.ActorRenderData.Ptr));
				AddSystemMessageString("TestActor.Inventory.CreatureID" @ string(TestActor.Inventory.CreatureID));
				AddSystemMessageString("TestActor.Inventory.Name" @ string(TestActor.Inventory.Name));
				AddSystemMessageString("TestActor.MeshInstance.Name" @ string(TestActor.MeshInstance.Name));				
				AddSystemMessageString("TestActor.GetMeshName." @ TestActor.GetMeshName());
				AddSystemMessageString("TestActor.GetURLMap()." @ TestActor.GetURLMap());
				

				AddSystemMessageString ("P.CharClassID" @ P.CharClassID);
				AddSystemMessageString ("P.bNpc" @ string(P.bNpc));	
				AddSystemMessageString ("P.NpcClassID" @ P.NpcClassID);
				AddSystemMessageString ("P.AttackItemClassID" @ string(P.AttackItemClassID));
				AddSystemMessageString ("P.DefenseItemClassID" @ string(P.DefenseItemClassID));
				AddSystemMessageString ("P.ShieldItemClassID" @ string(P.ShieldItemClassID));
				AddSystemMessageString ("P.CurWeaponType" @ string(P.CurWeaponType));
				AddSystemMessageString ("P.AttackItemVariationOption1" @ P.AttackItemVariationOption1);
				AddSystemMessageString ("P.AttackItemVariationOption2" @ P.AttackItemVariationOption2);
				AddSystemMessageString ("P.AttackItemEnchantedValue" @ P.AttackItemEnchantedValue);
							
				AddSystemMessageString ("P.NQuestList.Length" @ P.NQuestList.Length);
				AddSystemMessageString ("P.NQuestStepList.Length" @ P.NQuestStepList.Length);
				
				for (i = 0; i < P.NQuestList.Length; i++)
				{
					AddSystemMessageString ("P.NQuestList[" $ string(i) $ "]:" @ P.NQuestList[i]);
				}

				for (i = 0; i < P.NQuestStepList.Length; i++)
				{
					AddSystemMessageString ("P.NQuestStepList[" $ string(i) $ "]:" @ P.NQuestStepList[i]);
				}
								
				for (i = 0; i < P.NQuestList.Length; i++)
				{
					AddSystemMessageString ("P.GetQuestName" @ class'UIDATA_QUEST'.static.GetQuestName(P.NQuestList[i]));
				}		

				AddSystemMessageString("------------------------------------------------");
		
		}	
		*/	
			break;
			
		case "btnAlfaOff":  
			OnClickAlfaOffButton();
			break;
			
		case "btnTeleport": 				
			if( class'UIAPI_WINDOW'.static.IsShowWindow("TeleportBookMarkWnd") )
			{
				class'UIAPI_WINDOW'.static.HideWindow( "TeleportBookMarkWnd" );
			}
			else
			{
				DoAction(GetItemID(64));
			}
			break;
		///////////////// Local Map Manual Settings via Hidden 	RadarMapManual window (uncheck Hidden in xdat)	///////////
		case "btnApplyScale": 				
			arrCoefficient[2] = int(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtLocalScale"));
			break;
			
		case "btnApplyMag": 				
			rdr_RadarMapTex.SetMagnification(float(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtLocalMag")));
			rdr_RadarMapObject.SetMagnification(float(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtLocalMag")));
			break;
			
		case "btnApplyXY": 
		//	AddSystemMessageString("Player Position:" @ GetPlayerPosition());
			LocalMapX = int(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtLocalX"));
			LocalMapY = int(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtLocalY"));
			break;
			
		case "btnApplyEffect": 
		//	EffectViewer(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtEffectView"));
			break;
			
		case "btnDeleteEffect": 
		//	DestroyEffectViewer();
			break;
			
		case "btnIncEffect": 
			class'UIAPI_EDITBOX'.static.SetString("RadarMapWnd.RadarMapManual.edtEffectView", string(int(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtEffectView")) + 1));
			break;
			
		case "btnDecEffect": 
			class'UIAPI_EDITBOX'.static.SetString("RadarMapWnd.RadarMapManual.edtEffectView", string(int(class'UIAPI_EDITBOX'.static.GetString("RadarMapWnd.RadarMapManual.edtEffectView")) - 1));
			break;
			
		case "btnMeAddHunger": 
		//	AddMyHighLight();//(true);
		//	MeIsHunger();
			break;
			
		case "btnMeDelHunger": 
		//	CleanMyHighlight(1);
		//	MeIsFull();
			break;
	}
}


function bool isArmorName(string meshName)
{
	local int i;
	local int count;
	local string currentSymbol;
	
	
	count = 0;
	for (i = 0; i < Len(meshName); i++)
	{
		currentSymbol = Mid(meshName, i, 1);
		if (currentSymbol == "_")
		{
			count++;
		}
	}
	if (count >= 4)
		return true;
	else
		return false;
}	



function OnClickAlfaOnButton()
{
	btn_AlfaOn.HideWindow();
	SetINIBool("RadarMap", "TransparencyOn", false, "PatchSettings");
	btn_AlfaOff.ShowWindow();
	
	rdr_RadarMapTex.SetAlpha(255);
	tex_LocalMap.SetAlpha(255);
	tex_seaBg.SetAlpha(122);
}


function OnClickAlfaOffButton()
{
	btn_AlfaOff.HideWindow();
	SetINIBool("RadarMap", "TransparencyOn", true, "PatchSettings");
	btn_AlfaOn.ShowWindow();
	
	rdr_RadarMapTex.SetAlpha(150);
	tex_LocalMap.SetAlpha(150);
	tex_seaBg.SetAlpha(60);
}


function OnClickShowMeOnButton()
{
	btn_ShowMeOn.HideWindow();
	showMe = false;
	SetOptionBool( "Game", "radarShowMe", false );
	btn_ShowMeOff.ShowWindow();
	SetRadarElements();
}


function OnClickShowMeOffButton()
{
	btn_ShowMeOff.HideWindow();
	showMe = true;
	SetOptionBool( "Game", "radarShowMe", true );
	btn_ShowMeOn.ShowWindow();
	SetRadarElements();
}


function OnClickShowPartyOnButton()
{
	btn_ShowPartyOn.HideWindow();
	hideParty = true;
	ClearObject();
	SetOptionBool( "Game", "radarHideParty", true );
	btn_ShowPartyOff.ShowWindow();
}


function OnClickShowPartyOffButton()
{
	btn_ShowPartyOn.ShowWindow();
	hideParty = false;
	ClearObject();
	SetOptionBool( "Game", "radarHideParty", false );
	btn_ShowPartyOff.HideWindow();
}


function OnClickShowMobOnButton()
{
	btn_ShowMobOn.HideWindow();
	showMonster = false;
	ClearObject();
	SetOptionBool( "Game", "radarShowMonster", false );
	btn_ShowMobOff.ShowWindow();
}


function OnClickShowMobOffButton()
{
	btn_ShowMobOff.HideWindow();
	showMonster = true;
	ClearObject();
	SetOptionBool( "Game", "radarShowMonster", true );
	btn_ShowMobOn.ShowWindow();
}


function OnClickFixedOnButton()
{
	local int RadarFreeDiametr;
	local rect tempRect;
	btn_FixedOn.HideWindow();
	
	fixRadar = false;

	ClearTarget();
	ClearObject();
	
	SetOptionBool( "Game", "radarFix", false );
	btn_FixedOff.ShowWindow();
	
	if (show)
	{
		tex_LocalMap.HideWindow();
		show = false;	
	}
	
	tempRect = wnd_RadarMapRotation.GetRect();
	
	if (tempRect.nWidth >= tempRect.nHeight) // distanciya  udaleniya obe'ktov pri svobodnoi kamere (berem minimum)
	{

		RadarFreeDiametr = tempRect.nHeight;
	}
	else
	{
		RadarFreeDiametr = tempRect.nWidth;
	}
	
	rdr_RadarMapTex.SetWindowSize(squareMapSizeXY,squareMapSizeXY); // stavim razmeri kart radara i zon;
	rdr_RadarMapObject.SetWindowSize(RadarFreeDiametr - 6,RadarFreeDiametr - 6); 
	
	tex_LocalMap.SetWindowSize(squareMapSizeXY,squareMapSizeXY);		
	LocalMapRect = tex_LocalMap.GetRect();
	
	SetRadarElements();
}


function OnClickFixedOffButton()
{
	btn_FixedOff.HideWindow();
	
	fixRadar = true;
	
	ClearTarget();
	ClearObject();
	
	SetOptionBool( "Game", "radarFix", true );
	btn_FixedOn.ShowWindow();

	if (show)
	{
		tex_LocalMap.HideWindow();
		show = false;	
	}
	
	rdr_RadarMapTex.SetWindowSize(rectangleMapSizeX - 2,rectangleMapSizeY - 2); // stavim razmeri kart radara i zon;
	rdr_RadarMapObject.SetWindowSize(rectangleMapSizeX - 6,rectangleMapSizeY - 6);
	
	tex_LocalMap.SetWindowSize(rectangleMapSizeX - 2,rectangleMapSizeY - 2);
	LocalMapRect = tex_LocalMap.GetRect();	
		
	SetRadarElements();
	
	if (showMe) tex_myAngle.ShowWindow();
}


// 레이더 색상 관련
function HandleRadarZoneCode( int type )
{
	txt_boxMove.ShowWindow();
	tex_ZoneName.ShowWindow();
	txt_boxMove.SetAlpha(0);
	tex_ZoneName.SetAlpha(0);
	wnd_RadarMap.SetTimer(TIMER_ZONE_NAME, 3000);
	txt_boxMove.SetAlpha( 255, 0.8f );
	tex_ZoneName.SetAlpha( 150, 0.8f );
	
	SetTexZoneInfo(type);
	SetTexZoneColor(type);
}


// 존타이틀이 바뀔때 ID로 현재 미니맵을 가려줄지 결정한다. 
function HandleZoneTitle()
{
	local int nZoneID;
	local int isLargeMap;
	
	nZoneID = GetCurrentZoneID();
	
	if (txt_noMap.isShowWindow())	//존타이틀이 바뀔때는 항상 표시불가지역 텍스트를 가려준다. 
	{
		wnd_RadarMap.KillTimer(TIMER_ID3);
		txt_noMap.HideWindow();
	}
	
	StopLocal();
	txt_noMap.SetAlpha(0);
	
//	AddSystemMessageString("HandleZoneTitle"  @ nZoneID);
	
	if (IsLocalZone(nZoneID))
	{	
		if (tex_LocalMap.GetTextureName() != Right(LocalMapName, 3))	// if new local map uses
		{
			tex_LocalMap.SetWindowSize(LocalMapRect.nWidth, LocalMapRect.nHeight);		// when doing yeleport from map with Large flag, window size can reduce to 0,0;
		//	AddSystemMessageString("tex_LocalMap" @ tex_LocalMap.GetTextureName());
		//	AddSystemMessageString("LocalMapName" @ LocalMapName);
			rdr_RadarMapTex.SetMapInvisible(true);			
			rdr_RadarMapObject.SetMapInvisible(true);
			tex_LocalMap.HideWindow();
			show = false;
			tex_LocalMap.SetTexture(LocalMapName);
		}
			
		//////////// Manual Settings Local Map /////////////////////
		class'UIAPI_EDITBOX'.static.SetString("RadarMapWnd.RadarMapManual.edtLocalX", string(LocalMapX));
		class'UIAPI_EDITBOX'.static.SetString("RadarMapWnd.RadarMapManual.edtLocalY", string(LocalMapY));
		class'UIAPI_EDITBOX'.static.SetString("RadarMapWnd.RadarMapManual.edtLocalMag", localMag);
		class'UIAPI_EDITBOX'.static.SetString("RadarMapWnd.RadarMapManual.edtLocalScale", string(arrCoefficient[2]));
		///////////////////////////////////////////////////////////

		GetINIInt(string(nZoneID), "Large", isLargeMap, "LocalMap");
			
		if (isLargeMap == 1)
			wnd_RadarMap.SetTimer(TIMER_ID2_LARGE, TIMER_DELAY2);
		else
			wnd_RadarMap.SetTimer(TIMER_ID2, TIMER_DELAY2);
		
		if (zoneState == 0 || zoneState == 2 || zoneState == 3)		//0 - enter "zero" state, 1 - LocalMap; 2 - IsHideRadarZone; 3 - general map
		{
			magStep = 2;
			mag = float(localMag);
			
			CalculateSizeArray();
			
			rdr_RadarMapTex.SetMagnification(mag);
			rdr_RadarMapObject.SetMagnification(mag);
				
			ClearTarget();

			btn_Plus.DisableWindow();
			btn_Minus.DisableWindow();
			
			SetSliderAlpha(true);
			
			txt_noMap.SetText(GetCurrentZoneName());
			
			tex_rangeRadiusBig.HideWindow();
			tex_rangeRadiusSmall.HideWindow();
			SetRadiusTexture();
			
			zoneState = 1;
		}
	}		
	else
	{
		if (zoneState == 0 || zoneState == 1)
		{
			tex_LocalMap.HideWindow();
			tex_LocalMap.SetWindowSize(LocalMapRect.nWidth, LocalMapRect.nHeight);		// when doing yeleport from map with Large flag, window size can reduce to 0,0;
			tex_LocalMap.SetTexture("");
			
			magStep = prevmagStep;
			mag = arrMag[magStep];
			arrCoefficient[2] = 20;
			CalculateSizeArray();		
		
			ClearTarget();
							
			rdr_RadarMapTex.SetMagnification(mag);
			rdr_RadarMapObject.SetMagnification(mag);
			
			ClearTarget();
			
			btn_Plus.EnableWindow();
			btn_Minus.EnableWindow();
			
			SetSliderAlpha(false);
			
			tex_rangeRadiusBig.HideWindow();
			tex_rangeRadiusSmall.HideWindow();
			SetRadiusTexture();
		}
		
		if (IsHideRadarZone(nZoneID))
		{
			rdr_RadarMapTex.SetMapInvisible(true);
			txt_noMap.SetText(GetSystemString(1686));
			zoneState = 2;
		}
		else
		{
			rdr_RadarMapTex.SetMapInvisible(false);
			txt_noMap.SetText(GetCurrentZoneName());
			zoneState = 3;
		}
	}

	wnd_RadarMap.KillTimer(TIMER_ID3);
	wnd_RadarMap.SetTimer(TIMER_ID3, TIMER_DELAY3);	
	
	txt_noMap.ShowWindow();			
	txt_noMap.SetAlpha( 255, 0.8f );
}


function DrawNewRadar(int ResizeX, int ResizeY)
{
	local Rect oldSize;

	local int newSizeX;
	local int newSizeY;

	oldSize = wnd_RadarMapRotation.GetRect();
	newSizeX = oldSize.nX + oldSize.nWidth - ResizeX + 4;
	newSizeY = ResizeY - oldSize.nY + 4;
	

	if (newSizeX < 200) newSizeX = 200;
	if (newSizeX > 400) newSizeX = 400;
	if (newSizeY < 200) newSizeY = 200;
	if (newSizeY > 400) newSizeY = 400;

	if ((newSizeX) % 2 != 0) newSizeX = newSizeX - 1;
	if ((newSizeY) % 2 != 0) newSizeY = newSizeY - 1;
	
	
	wnd_RadarMap.SetWindowSize(newSizeX, 29);
	wnd_RadarMap.MoveTo(oldSize.nX + oldSize.nWidth - newSizeX, oldSize.nY);
	
	wnd_RadarMapRotation.SetWindowSize(newSizeX, newSizeY);		// novoe znachenie razmera po X okna s ramkoi
	wnd_RadarSettings.SetWindowSize(newSizeX, newSizeY);		// novoe znachenie razmera po X okna s knopkami		
		
	ResizeRadar();	
}

function CalculateSizeArray()
{
	local Rect tempRect;
	local int i;
	
	tempRect = wnd_RadarMapRotation.GetRect();
	
	for(i = 0; i <= MAX_MAG; i++)
	{
		fixedRangeDrawX[i] = (tempRect.nWidth/2)*arrCoefficient[i]; 	
		fixedRangeDrawY[i] = (tempRect.nHeight/2)*arrCoefficient[i];
		
		fixedScanRange[i] = (sqrt(tempRect.nWidth*tempRect.nWidth + tempRect.nHeight*tempRect.nHeight)/2) * arrCoefficient[i];
		if (fixedScanRange[i] > 4000)
			fixedScanRange[i] = 4000;
		
		if (fixedRangeDrawX[i] >= fixedRangeDrawY[i]) // distanciya  udaleniya obe'ktov pri svobodnoi kamere (berem minimum)
		{
			freeRangeDrawXY[i] = fixedRangeDrawY[i];
		}
		else
		{
			freeRangeDrawXY[i] = fixedRangeDrawX[i];
		}
		
		if (freeRangeDrawXY[i] > 4000)
			freeScanRange[i] = 4000;
		else
			freeScanRange[i] = freeRangeDrawXY[i];
		
		if (i == 0) 
		{
			freeScanRange[i] = 2000;
			fixedScanRange[i] = 2000;
		}	
	}
}

function ResizeRadar()
{
	local Rect tempRect;

	local int change;
	local int RadarFreeDiametr;
	local int tempX, tempY;
	
	local ItemInfo ArrowItem;
	
	ClearObject();
	ClearTarget();
	
	tempRect = wnd_RadarMapRotation.GetRect();

	itm_ResizeArrow.Clear();
	ArrowItem.IconName = "MonIcaEssence.RadarMapWnd.tex_resize_item";
	ArrowItem.Name = "Width:" @ string(tempRect.nWidth) @ "Height:" @ string(tempRect.nHeight);
	itm_ResizeArrow.AddItem(ArrowItem);
	
	rectangleMapSizeX = tempRect.nWidth;
	rectangleMapSizeY = tempRect.nHeight;
	
	CalculateSizeArray();
	
	if (tempRect.nWidth >= tempRect.nHeight) // distanciya  udaleniya obe'ktov pri svobodnoi kamere (berem minimum)
	{
		change = tempRect.nWidth; // vibiraem bolshuy storonu okna s ramkoi t.k. karti doljni bit kvadratnie
		RadarFreeDiametr = tempRect.nHeight;
	}
	else
	{
		change = tempRect.nHeight;
		RadarFreeDiametr = tempRect.nWidth;
	}
		
	tempX = sqrt(2 * tempRect.nHeight * tempRect.nHeight); // zapominaem razmeri storon dlya okna pryamougolnoi maski
	tempY = sqrt(2 * tempRect.nWidth * tempRect.nWidth);
	
	squareMapSizeXY = sqrt(2 * change * change); // razmeri storon kart radara i gorodov;	
	
	if (squareMapSizeXY%2 != 0) squareMapSizeXY = squareMapSizeXY -1; // razmeri storon doljni bit' kratni 2


	if (fixRadar)
	{
		rdr_RadarMapTex.SetWindowSize(rectangleMapSizeX - 2,rectangleMapSizeY - 2); // stavim razmeri kart radara i zon;
		rdr_RadarMapObject.SetWindowSize(rectangleMapSizeX - 6,rectangleMapSizeY - 6);
		
		tex_LocalMap.SetWindowSize(rectangleMapSizeX - 2,rectangleMapSizeY - 2);
	}
	else
	{
		rdr_RadarMapTex.SetWindowSize(squareMapSizeXY, squareMapSizeXY); // stavim razmeri kart radara i zon;
		rdr_RadarMapObject.SetWindowSize(RadarFreeDiametr - 6,RadarFreeDiametr - 6); // test var
		
		tex_LocalMap.SetWindowSize(squareMapSizeXY,squareMapSizeXY);		
	}


	LocalMapRect = tex_LocalMap.GetRect();
	wnd_SquareMask.SetWindowSize(sqrt(2 * squareMapSizeXY * squareMapSizeXY), sqrt(2 * squareMapSizeXY * squareMapSizeXY)); // stavim razmeri okna dlya kvadratnoi maski
	wnd_RectangleMask.SetWindowSize(sqrt(2 * tempY * tempY), sqrt(2 * tempX * tempX)); // stavim razmeri okna dlya pryamougolnoi maski	

	tex_rangeRadiusBig.HideWindow();
	tex_rangeRadiusSmall.HideWindow();
	SetRadiusTexture();
	
	SetINIInt("RadarMap", "width", tempRect.nWidth, "PatchSettings");
	SetINIInt("RadarMap", "height", tempRect.nHeight, "PatchSettings");
}


function RecoveryRadar()
{
	local int x;
	local int y;

	GetINIInt("RadarMap", "width", x, "PatchSettings");
	GetINIInt("RadarMap", "height", y, "PatchSettings");

	wnd_RadarMap.SetWindowSize(x, 29);	
	wnd_RadarMapRotation.SetWindowSize(x, y);
	wnd_RadarSettings.SetWindowSize(x, y);
	ResizeRadar();	
}


function OnLButtonDown( WindowHandle a_WindowHandle, int X, int Y )
{	
	if (a_WindowHandle == itm_ResizeArrow) //
	{
		wnd_RadarMap.SetTimer(TIMER_SHOW_RESIZE, 500);
	}	
}


function OnLButtonUp( WindowHandle a_WindowHandle, int X, int Y )
{
	if 	(wnd_ResizeArea.isShowWindow())
	{
		wnd_ResizeArea.HideWindow();
		itm_ResizeArrow.ShowWindow();
	}
}


function OnDropItem( String strID, ItemInfo infItem, int x, int y )
{
	DrawNewRadar(x,y);
}


function OnMouseOver (WindowHandle a_WindowHandle )
{
	if	(a_WindowHandle == tex_DetectCenter)
	{
		wnd_RadarSettings.ShowWindow();
		
		tex_ControlFrame.ShowWindow();
		tex_DetectDown.ShowWindow();
		tex_DetectUp.ShowWindow();
		tex_DetectLeft.ShowWindow();
		tex_DetectRight.ShowWindow();
		tex_DetectCenter.HideWindow();
	}
	else if (a_WindowHandle == tex_DetectLeft || a_WindowHandle == tex_DetectRight || a_WindowHandle == tex_DetectUp || a_WindowHandle == tex_DetectDown)
	{
		wnd_RadarSettings.HideWindow();

		tex_ControlFrame.HideWindow();
		tex_DetectDown.HideWindow();
		tex_DetectUp.HideWindow();
		tex_DetectLeft.HideWindow();
		tex_DetectRight.HideWindow();
		tex_DetectCenter.ShowWindow();
	}
	else if (a_WindowHandle == tex_ControlFrame)
	{
		wnd_RadarMap.SetDraggable(true);
	}
	else if (a_WindowHandle == tex_ZoneIcon)
	{
		CreateZoneTooltip();
	}	
}


function OnMouseOut (WindowHandle a_WindowHandle)
{
	if	(a_WindowHandle == tex_ControlFrame)
	{
		wnd_RadarMap.SetDraggable(false);
	}
}

function CreateZoneTooltip ()	//	ZoneIcon texture tooltip
{
	local CustomTooltip ZoneTooltip;
	
	ZoneTooltip.MinimumWidth = 144;
	ZoneTooltip.DrawList.length = 4;
		
	ZoneTooltip.DrawList[0].eType = DIT_TEXT;
	ZoneTooltip.DrawList[0].nOffSetX = 1;
	ZoneTooltip.DrawList[0].nOffSetY = 1;
	ZoneTooltip.DrawList[0].t_bDrawOneLine = true;
	ZoneTooltip.DrawList[0].t_strText = GetCurrentZoneName();
	
	ZoneTooltip.DrawList[1].eType = DIT_TEXT;
	ZoneTooltip.DrawList[1].bLineBreak = true;
	ZoneTooltip.DrawList[1].t_bDrawOneLine = true;
	ZoneTooltip.DrawList[1].nOffSetX = 1;
	ZoneTooltip.DrawList[1].nOffSetY = 2;
	ZoneTooltip.DrawList[1].t_color = ZoneTooltipColor;
	ZoneTooltip.DrawList[1].t_color.A = 255;
	ZoneTooltip.DrawList[1].t_strText = ZoneTooltipArea;
	
	ZoneTooltip.DrawList[2].eType = DIT_SPLITLINE;
	ZoneTooltip.DrawList[2].bLineBreak = true;
	ZoneTooltip.DrawList[2].t_bDrawOneLine = true;
	ZoneTooltip.DrawList[2].nOffSetY = 2;
	ZoneTooltip.DrawList[2].u_nTextureWidth = ZoneTooltip.MinimumWidth;					
	ZoneTooltip.DrawList[2].u_nTextureHeight = 1;
	ZoneTooltip.DrawList[2].u_strTexture ="L2ui_ch3.tooltip_line";

	
	ZoneTooltip.DrawList[3].eType = DIT_TEXT;
	ZoneTooltip.DrawList[3].bLineBreak = true;
	ZoneTooltip.DrawList[3].t_bDrawOneLine = true;
	ZoneTooltip.DrawList[3].nOffSetX = 1;
	ZoneTooltip.DrawList[3].nOffSetY = 1;
	ZoneTooltip.DrawList[3].t_color.R = 122;
	ZoneTooltip.DrawList[3].t_color.G = 122;
	ZoneTooltip.DrawList[3].t_color.B = 122;
	ZoneTooltip.DrawList[3].t_color.A = 255;
	ZoneTooltip.DrawList[3].t_strText = Left(GetTimeString(),5);
	
	tex_ZoneIcon.SetTooltipCustomType(ZoneTooltip);
}



function SetTexZoneColor (int type)
{
	switch (type)
	{
		//Ordinary Field: Grey
		case EDGE_GRAY:
		tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd.tex_grey_zone");
		break;
		
		//Peace Zone: Blue
		case EDGE_BLUE:
		tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd.tex_blue_zone");
		break;
		
		//Siege Warfare Zone: Orange
		case EDGE_ORANGE:
		tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd.tex_orange_zone");
		break;
		
		//Buff Zone: Green
		case EDGE_BUFFRED:
		tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd.tex_red_zone");
		break;
		
		//DeBuff Zone: Red
		case EDGE_RED:
		tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd.tex_red_zone");
		break;
		
		//SSQZone: Grey
		case EDGE_SSQGRAY:
		tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd.tex_grey_zone");
		break;
		
		//PVPZone: Green
		case EDGE_PVPGREEN:
		tex_ZoneIcon.SetTexture("MonIcaEssence.RadarMapWnd.tex_green_zone");		
		break;
	}
}


function SetTexZoneInfo (int type)
{
	local Color msgColor;
	
	switch (type)
	{
		//Ordinary Field: Grey
		case EDGE_GRAY:
		msgColor.R = 220;
		msgColor.G = 220;
		msgColor.B = 220;
		txt_boxMove.SetTextColor(msgColor);
		txt_boxMove.SetText(GetSystemString(1284));
		ZoneTooltipArea = GetSystemString(1284);
		ZoneTooltipColor = msgColor;
		break;
		
		//Peace Zone: Blue
		case EDGE_BLUE:
		msgColor.R = 18;
		msgColor.G = 128;
		msgColor.B = 255;
		txt_boxMove.SetTextColor(msgColor);
		txt_boxMove.SetText(GetSystemString(1285));
		ZoneTooltipArea = GetSystemString(1285);
		ZoneTooltipColor = msgColor;
		break;
		
		//Siege Warfare Zone: Orange
		case EDGE_ORANGE:
		msgColor.R = 255;
		msgColor.G = 147;
		msgColor.B = 28;
		txt_boxMove.SetTextColor(msgColor);
		txt_boxMove.SetText(GetSystemString(1286));
		ZoneTooltipArea = GetSystemString(1286);
		ZoneTooltipColor = msgColor;
		break;
		
		//Buff Zone: Green
		case EDGE_BUFFRED:
		msgColor.R = 255;
		msgColor.G = 11;
		msgColor.B = 11;
		txt_boxMove.SetTextColor(msgColor);
		txt_boxMove.SetText(GetSystemString(1287));
		ZoneTooltipArea = GetSystemString(1287);
		ZoneTooltipColor = msgColor;
		break;
		
		//DeBuff Zone: Red
		case EDGE_RED:
		msgColor.R = 242;
		msgColor.G = 11;
		msgColor.B = 11;
		txt_boxMove.SetTextColor(msgColor);
		txt_boxMove.SetText(GetSystemString(1288));
		ZoneTooltipArea = GetSystemString(1288);
		ZoneTooltipColor = msgColor;
		break;
		
		//SSQZone: Grey
		case EDGE_SSQGRAY:
		msgColor.R = 220;
		msgColor.G = 220;
		msgColor.B = 220;
		txt_boxMove.SetTextColor(msgColor);
		txt_boxMove.SetText(GetSystemString(1289));
		ZoneTooltipArea = GetSystemString(1289);
		ZoneTooltipColor = msgColor;
		break;
		
		//PVPZone: Green
		case EDGE_PVPGREEN:
		msgColor.R = 63;
		msgColor.G = 249;
		msgColor.B = 0;
		txt_boxMove.SetTextColor(msgColor);
		txt_boxMove.SetText( GetSystemString(1290));
		ZoneTooltipArea = GetSystemString(1290);
		ZoneTooltipColor = msgColor;
		break;
	}
	

}


function bool IsLocalZone (int nZoneID)
{
	local string temp;
	
	localMag = "";
	LocalMapName = "";
	GetINIString(string(nZoneID), "LocalMapName", LocalMapName, "LocalMap");
	
	if (LocalMapName != "")
	{
		GetINIInt(string(nZoneID), "LocalMapX", LocalMapX, "LocalMap");
		GetINIInt(string(nZoneID), "LocalMapY", LocalMapY, "LocalMap");	
		GetINIString(string(nZoneID), "LocalMag", localMag, "LocalMap");
		GetINIString(string(nZoneID), "LocalScale", temp, "LocalMap");
		arrCoefficient[2] = int(temp);
		return true;
	}
	else
	{
		return false;
	}
}


//미니맵을 가릴 지역이면 true 리턴. 보여줄 지역이면 false 리턴
function bool IsHideRadarZone (int nZoneID)
{	
	//debug("nZoneID : " $ nZoneID);
	switch(nZoneID)
	{
		//레이더를 가릴 곳들
		case 17:
		case 28:
		case 32:
		case 33:
		case 45:
		case 54:
		case 55:
		case 56:
		case 60:
		case 65:
		case 66:
		case 69:
		case 79:
		case 80:
		case 81:
		case 82:
		case 83:
		case 84:
		case 85:
		case 86:
		case 87:
		case 88:
		case 89:
		case 90:
		case 91:
		case 92:
		case 110:
		case 114:
		case 119:
		case 121:
		case 125:
		case 126:
		case 127:
		case 128:
		case 129:
		case 130:
		case 131:
		case 132:
		case 133:
		case 134:
		case 135:
		case 136:
		case 137:
		case 138:
		case 139:
	//	case 140:	//Disciples Necropolis
		case 178:
		case 142:
		case 143:
		case 145:
		case 152:
		case 159:
		case 162:
		case 164:
		case 165:
	//	case 177:	//Disciples Necropolis dubl'?
		case 178:
		case 185:
		case 273:
		case 274:
		case 275:
		case 276:
		case 278:
		case 279:
		case 281:		//4대영묘
		case 282:
		case 283:
		case 284:
		case 285:
		case 286:		// 4대영묘
		case 303:
		case 304:
		case 306:
		case 311:		// 올림피아드 경기장
		case 316:
		case 317:
		case 318:
		case 319:
		case 320:
		case 321:
		case 336:
		case 339:
		case 340:		// 대현자의 영묘
		case 341:
		case 358:
		case 359:
		case 360:
		case 363:		//헬바운드
		case 364:		//헬바운드
		case 365:
		case 366:
		case 367:
		case 368:
		case 369:	
		case 370:
		case 371:
		case 372:
		case 373:
		case 374:
		case 375:
		case 376:
		case 377:
		case 378:		//나이아의 탑
		case 379:		//강철의 성		//CT 1.5
		case 380:		//강철의 성		
		case 381:		//강철의 성		//CT 1.5
		case 387:		//암운의 저택		//CT 1.5
		case 390:		//수정의 신탁소
		case 392:		//산호의 정원
		case 393:		//암운의 저택
		case 394:		//에메랄드 광장
		case 396:		//증기의길
		case 398:		//산호의 정원
		case 400:		//산호의 정원
		case 401:		//에메랄드 광장
		case 410:		//노르닐의 동굴
		case 412:		//노르닐의 정원
		case 443:		//지하 수용소
		case 452:		//지하 콜로세움
		case 453:		// 공간의 탑
		case 454:		// 공간의 탑
		case 455:		// 톨레스의 공작소
		case 456:		// 톨레스의 공작소
		case 457:		// 나이아의 탑
		case 458:		// 나이아의 탑
		case 459:		//지하 수용소
		case 460:		//카마로카
		case 461:		
		case 462:		
		case 463:		
		case 464:		
		case 465:		
		case 466:		//카마로카
		case 467:		// TTP 27672
		case 472:		//악마섬
		case 473:		//해적들의 터널
		case 474:		//크라테의 큐브
		case 475:		//크라테의 큐브
		case 476:		//크라테의 큐브
		case 490:
			return true;		
		default:
			return false;
	}		
}


//////////////FLIGHT STUFF///////////////

// 비행정 탑승 스테이트를 알려준다. 
function OnAirShipState( string a_Param )
{
	local int VehicleID;
	local int IsDriver;
	//~ local UserInfo info;
	//~ local string ClanName;
	local vehicle myAirShip;
	
	ParseInt( a_Param, "VehicleID", VehicleID );
	ParseInt( a_Param, "IsDriver", IsDriver );
	
	myAirShip = class'VehicleAPI'.static.GetVehicle( VehicleID );
	
	if (myAirShip.MaxFuel > 0)
	{
		isFreeShip = true;
		barFuel.SetPoint(myAirShip.CurFuel, myAirShip.MaxFuel);	// 연료를 업데이트 한다. 
		
		if (myAirShip.CurFuel == 0)	
		{	
			AddSystemMessage(2464);	//	바닥남
		}		
		else if  (myAirShip.CurFuel < 50)
		{	
			AddSystemMessage(2463);	//	곧 바닥남
		}	
		
		ShipNameTxt.SetText(  GetSystemMessage(2454) );	//크레스니크급 비행선
	}
	else
	{
		isFreeShip = false;
	}
		
	if (VehicleID > 0)	// 비행정에 탑승했을 경우
	{
		if(isFreeShip)
		{
			if(!GetOptionBool( "Game", "SystemTutorialBox" ))
			{
				// 여기서 타이머를 켠다.
				if (!isOnFSTimer)	// 타이머가 켜져있지 않을때만 켠다.
				{	
					isOnFSTimer = true;
					ShowAirShipTutorial(2742);
					ShowAirShipTutorial(2741);
					wnd_RadarMap.SetTimer( FS_TIMER_ID,FS_TIME );
				}
				else
				{
					if (IsDriver > 0)
					{
						ShowAirShipTutorial(2494);	// 시스템 메세지로 간단히 설명해준다.
						ShowAirShipTutorial(2495);
					}
				}
			}		
		
			if(!FlightStatusGauges.isShowWindow()) FlightStatusGauges.ShowWindow();		//게이지들 보여주기
		}
	}
	else	// 비행정에서 내렸을 경우
	{		
		isFreeShip = false;
		
		if (isOnFSTimer) wnd_RadarMap.KillTimer( FS_TIMER_ID );		// 타이머가 켜져있으면 끈다.
		isOnFSTimer = false;

		if (FlightStatusGauges.isShowWindow()) FlightStatusGauges.HideWindow();		//게이지들 숨김
	}			
}


// 비행정 정보 업데이트
function OnAirShipUpdate( string a_Param )
{
	local vehicle myAirShip;
	local int VehicleID;
	
	ParseInt( a_Param, "VehicleID", VehicleID );
	
	myAirShip = class'VehicleAPI'.static.GetVehicle( VehicleID );
	
	//barHP.SetValue( myAirShip.CurHP, myAirShip.MaxHP);	// 추후 HP가 들어가면 주석을 해제!
	
	if(myAirShip.MaxFuel > 0)
	{		
		barFuel.SetPoint(myAirShip.CurFuel, myAirShip.MaxFuel);	// 연료를 업데이트 한다. 
		if (myAirShip.CurFuel == 0)	
		{
			AddSystemMessage(2464);	//	바닥남
		}
		else if (myAirShip.CurFuel < 50)
		{
			AddSystemMessage(2463);	//	곧 바닥남
		}
	}
}


function ShowAirShipTutorial( int SystemMsgID)
{
	local int RandVal;
	local int RandSystemMsgID;
	
	if(SystemMsgID < 0)	// 0보다 작으면 랜덤한 시스템 메세지를 보여준다. 
	{
		RandVal = Rand(8);
		
		RandSystemMsgID = 2494;	// 만약을 위해 디폴트값 -_-
		
		switch(RandVal)
		{
			case 0:	RandSystemMsgID = 2494;		break;		// 각각의 시스템 메세지 아이디를 적어준다
			case 1:	RandSystemMsgID = 2495;		break;
			case 2:	RandSystemMsgID = 2496;		break;
			case 3:	RandSystemMsgID = 2497;		break;
			case 4:	RandSystemMsgID = 2498;		break;
			case 5 :	RandSystemMsgID = 2741;		break;
			case 6:	RandSystemMsgID = 2742;		break;
			case 7:	RandSystemMsgID = 2422;		break;
		}
	}
	else
	{
		RandSystemMsgID = SystemMsgID;
	}
	
	if(!GetOptionBool( "Game", "SystemTutorialBox" ))	// 시스템 튜토리얼 체크박스를 확인해 주어야 한다. 
	{	
		AddSystemMessage(RandSystemMsgID);	// 시스템 메세지 추가
	}
	else
	{
		isOnFSTimer = false;
		wnd_RadarMap.KillTimer( FS_TIMER_ID );	// 타이머를 죽여준다.
	}
}


// 비행정 관련 함수들
// 비행정 게이지 초기화
function FlightGaugesClear()
{
	barHP.SetValue(100, 100);
	barMP.SetValue(100, 100);
	
	barHP.SetAlpha(100);
	barMP.SetAlpha(100);
	barFuel.SetPoint(0, 0);
}



/*function ScanActor()
{
	local UserInfo tempObject;
	local vector testVector;
	local Actor PlayerActor;
	local Inventory InventoryA;
//	local Object O;
	local Actor A;
	local Weapon W;
	
	local Pawn P;
	local L2Pickup L2P;
	local Pickup Pick;
	local LineagePlayerController C;
	local PlayerController PC;
	local rotator defaultRotion1;
	local array<int> arrProperty;
	local ItemID IDDQD;
	
	PlayerActor = GetPlayerActor();	

	foreach PlayerActor.RadiusActors( class 'Actor', A, 444, PlayerActor.Location)
	{	
				if (GetDistanceFromMe(A.Location.X, A.Location.Y) < 200) continue;
			AddSystemMessageString("----------------ScanActor-----------");//Class OwnerNameMeshContainer		
			//	if (A.Tag == 'L2Pickup')
			//	{
				Pick = Pickup(A);
					W = Weapon(A);
					Pick.RespawnEffect();
					AddSystemMessageString("ScanActor:" @ class'UIDATA_TRANSFORM'.static.GetNpcID( A.CreatureID, 0));
					AddSystemMessageString("ScanActor:" @ "A.CreatureID" @ A.CreatureID);
					AddSystemMessageString("ScanActor:" @ "W.PickupAmmoCount" @ W.PickupAmmoCount);
					AddSystemMessageString("ScanActor:" @ "Pick.PickupForce" @ Pick.PickupForce);
					AddSystemMessageString("ScanActor: Pick.PickupMessage" @ Pick.PickupMessage);
					AddSystemMessageString("ScanActor:" @ "A.CreatureID" @ PlayerActor.Level.ObjectPool.Objects.Length);
					L2P = L2Pickup(A);
					
					InventoryA = Inventory(A);
					IDDQD = GetItemID(-1);
					IDDQD.ServerID =  A.CreatureID;
					AddSystemMessageString("ScanActor: GetNPCName" @ class'UIDATA_NPC'.static.GetNPCName(A.CreatureID));
					AddSystemMessageString("ScanActor: GetNpcProperty" @ class'UIDATA_NPC'.static.GetNpcProperty(A.CreatureID, arrProperty));
					AddSystemMessageString("ScanActor: GetNpcProperty Length" @ arrProperty.Length);
					AddSystemMessageString("ScanActor: GetItemName" @ class'UIDATA_ITEM'.static.GetItemName(IDDQD) );
					class'UIDATA_PAWNVIEWER'.static.ToggleAttackMode();
					class'UIDATA_PAWNVIEWER'.static.AttackTarget();
					P = Pawn(A);
					
					AddSystemMessageString("ScanActor:" @ "A.ObjectFlags" @ A.ObjectFlags);
					AddSystemMessageString("ScanActor:" @ "A.CacheIndex" @ A.CacheIndex);
					AddSystemMessageString("ScanActor:" @ "A.HashNextBuffer" @ A.HashNextBuffer);
					AddSystemMessageString("ScanActor:" @ "A.IndexBuffer" @ A.IndexBuffer);
					AddSystemMessageString("ScanActor:" @ "A.Outer.Name" @ A.Outer.Name);
					AddSystemMessageString("ScanActor:" @ "A.Outer.Class.Name" @ A.Outer.Class.Name);
					
					AddSystemMessageString("ScanActor:" @ "InventoryA.ItemName" @ InventoryA.ItemName);
					AddSystemMessageString("ScanActor:" @ "InventoryA.ThirdPersonActor.CreatureID" @ InventoryA.ThirdPersonActor.CreatureID);
					AddSystemMessageString("ScanActor:" @ "InventoryA.ThirdPersonActor.Name" @ InventoryA.ThirdPersonActor.Name);	
				
					
					AddSystemMessageString("ScanActor:" @ "P.CharClassID" @ P.CharClassID);
					AddSystemMessageString("ScanActor:" @ "P.NpcClassID" @ P.NpcClassID);
					AddSystemMessageString("ScanActor:" @ "P.AttackItemClassID" @  P.AttackItemClassID);
					AddSystemMessageString("ScanActor:" @ "P.DefenseItemClassID" @ P.DefenseItemClassID);
					AddSystemMessageString("ScanActor:" @ "P.ShieldItemClassID" @ P.ShieldItemClassID);
					
					AddSystemMessageString("ScanActor:" @ "A.Class.Name" @ A.Class.Name);
			
					AddSystemMessageString("ScanActor:" @ "A.Instigator.Name" @ A.Instigator.Name);
					AddSystemMessageString("ScanActor:" @ "A.Instigator.Tag" @ A.Instigator.Tag);
					AddSystemMessageString("ScanActor:" @ "A.Instigator.CharClassID" @ A.Instigator.CharClassID);
					AddSystemMessageString("ScanActor:" @ "A.Instigator.NpcClassID" @ A.Instigator.NpcClassID);
					AddSystemMessageString("ScanActor:" @ "A.Instigator.AttackItemClassID" @  A.Instigator.AttackItemClassID);
					AddSystemMessageString("ScanActor:" @ "A.Instigator.DefenseItemClassID" @ A.Instigator.DefenseItemClassID);
					AddSystemMessageString("ScanActor:" @ "A.Instigator.ShieldItemClassID" @ A.Instigator.ShieldItemClassID);
					
			//	}
	}
}
	//		AddSystemMessageString("ScanActor:" @ "A.MeshContainer.FirstMeshComponent.Owner.Name" @ A.MeshContainer.FirstMeshComponent.Owner.Name);
	//		AddSystemMessageString("ScanActor:" @ "A.MeshContainer.MeshComponentBufferArray.Ptr" @ A.MeshContainer.MeshComponentBufferArray.Ptr);
//			AddSystemMessageString("ScanActor:" @ "A.MeshContainer.MeshComponentArray.Ptr" @ A.MeshContainer.MeshComponentArray.Ptr);
	//		AddSystemMessageString("ScanActor:" @ "A.MeshContainer.Owner.Tag" @ A.MeshContainer.Owner.Tag);
	//		AddSystemMessageString("ScanActor:" @ "A.MeshContainer.Owner.Name" @ A.MeshContainer.Owner.Name);
	//		AddSystemMessageString("ScanActor:" @ "A.MeshContainer.Name" @ A.MeshContainer.Name);
	//		AddSystemMessageString("ScanActor:" @ "A.MeshContainer.Tag" @ A.MeshContainer.Tag);
			AddSystemMessageString("ScanActor:" @ "A.Child.Length" @ A.Child.Length);
			AddSystemMessageString("ScanActor:" @ "A.Child[0].Name" @ A.Child[0].Name);
			AddSystemMessageString("ScanActor:" @ "A.Child[0].Tag" @ A.Child[0].Tag);
			AddSystemMessageString("ScanActor:" @ "A.Child[1].Name" @ A.Child[1].Name);
			AddSystemMessageString("ScanActor:" @ "A.Child[1].Tag" @ A.Child[1].Tag);
			AddSystemMessageString("ScanActor:" @ "A.Touching.Length" @ A.Touching.Length);
			AddSystemMessageString("ScanActor:" @ "A.Touching[0].Name" @ A.Touching[0].Name);
			AddSystemMessageString("ScanActor:" @ "A.Touching[0].Tag" @ A.Touching[0].Tag);	
			AddSystemMessageString("ScanActor:" @ "A.Touching[1].Name" @ A.Touching[1].Name);
			AddSystemMessageString("ScanActor:" @ "A.Touching[1].Tag" @ A.Touching[1].Tag);
			AddSystemMessageString("ScanActor:" @ "A.Touching[2].Name" @ A.Touching[2].Name);
			AddSystemMessageString("ScanActor:" @ "A.Touching[2].Tag" @ A.Touching[2].Tag);	
			AddSystemMessageString("ScanActor:" @ "A.Touching[3].Name" @ A.Touching[3].Name);
			AddSystemMessageString("ScanActor:" @ "A.Touching[3].Tag" @ A.Touching[3].Tag);				
			AddSystemMessageString("ScanActor:" @ "A.Group" @ A.Group);
			AddSystemMessageString("ScanActor:" @ "A.L2MoveEvent" @ A.L2MoveEvent);
			AddSystemMessageString("ScanActor:" @ "A.InitialState" @ A.InitialState);
			AddSystemMessageString("ScanActor:" @ "A.JoinedTag" @ A.JoinedTag);
			AddSystemMessageString("ScanActor:" @ "A.CollisionTag" @ A.CollisionTag);
			AddSystemMessageString("ScanActor:" @ "A.ActorRenderData.Ptr" @ A.ActorRenderData.Ptr);
			AddSystemMessageString("ScanActor:" @ "A.LightRenderData.Ptr" @ A.LightRenderData.Ptr);
			AddSystemMessageString("ScanActor:" @ "A.Class.Name" @ A.Class.Name);
			AddSystemMessageString("ScanActor:" @ "A.ForcedVisibilityZoneTag" @ A.ForcedVisibilityZoneTag);
			AddSystemMessageString("ScanActor:" @ "A.CreatureID" @ A.CreatureID);
			AddSystemMessageString("ScanActor:" @ "A.Name" @ A.Name);
			AddSystemMessageString("ScanActor:" @ "A.Tag" @ A.Tag);
			AddSystemMessageString("ScanActor:" @ "A.NMoverActor.Ptr" @ A.NMoverActor.Ptr);
			AddSystemMessageString("ScanActor:" @ "A.L2NMover.Name" @ A.L2NMover.Name);
			AddSystemMessageString("ScanActor:" @ "A.L2NMover.Tag" @ A.L2NMover.Tag);
			AddSystemMessageString("ScanActor:" @ "A.CollisionHeight" @ A.CollisionHeight);
			P = Pawn(A);
			AddSystemMessageString("ScanActor:" @ "P.CharClassID" @ P.CharClassID);
			AddSystemMessageString("ScanActor:" @ "P.NpcClassID" @ P.NpcClassID);
			AddSystemMessageString("ScanActor:" @ "P.AttackItemClassID" @  P.AttackItemClassID);
			AddSystemMessageString("ScanActor:" @ "P.DefenseItemClassID" @ P.DefenseItemClassID);
			AddSystemMessageString("ScanActor:" @ "P.ShieldItemClassID" @ P.ShieldItemClassID);
			AddSystemMessageString("ScanActor:" @ "P.Privates.Name.Length" @ P.Privates.Length);	
			AddSystemMessageString("ScanActor:" @ "P.Privates[0].Name" @ P.Privates[0].Name);	
			AddSystemMessageString("ScanActor:" @ "P.Privates[0].ai" @ P.Privates[0].ai);		
			AddSystemMessageString("ScanActor:" @ "P.ai" @ P.ai);	
			AddSystemMessageString("ScanActor:" @ "P.nickname" @ P.nickname);					
			AddSystemMessageString("ScanActor:" @ "P.OwnerName" @ P.OwnerName);
			AddSystemMessageString("ScanActor:" @ "P.RidePawn.CreatureID" @ P.RidePawn.CreatureID);
			AddSystemMessageString("ScanActor:" @ "P.RidePawn.Name" @ P.RidePawn.Name);
			AddSystemMessageString("ScanActor:" @ "P.RidePawn.Tag" @ P.RidePawn.Tag);
			AddSystemMessageString("ScanActor:" @ "P.CurRideType" @ P.CurRideType);
			AddSystemMessageString("ScanActor:" @ "P.RidePawn.CurRideType" @ P.RidePawn.CurRideType);
	//		AddSystemMessageString("PARTY NA KONE" @ "P.L2NMover.Name" @ P.L2NMover.Name);
			AddSystemMessageString("--------------------------------------------------");
			GetUserInfo(A.CreatureID, tempObject);
						
						
			defaultRotion1 = defaultRotion;
			defaultRotion1.Pitch = 11111;		// sverhu vniz ()
			
			if (tempObject.bPet)
			{
				P = Pawn(A);
				AddSystemMessageString("ScanActor: ETO PET");//bSetSizeScale
				P.bIsHero = true;//var Emitter								NQuestMarkEffect;
				P.NHeroEffect = P.Spawn(PartyMemberHLEmitterClass, P, , P.Location, defaultRotion); // ne ischezaet pri smeti
				P.NQuestMarkEffect = P.Spawn(HLEmitterClassArmor, P, , P.Location, defaultRotion);	// ischezaet pri smeti
				P.NSpoilEffect = P.Spawn(HLEmitterClassWeapon, P, , P.Location, defaultRotion1);		// ne ischezaet pri smeti
				P.NQuestMarkEffect.SetBase(P);
				P.NSpoilEffect.SetBase(P);
			//		P.NHeroEffect.bSetSizeScale=true;
			P.DamageEffect = PartyLeaderHLEmitterClass;
			P.HungerEmitter.SetPhysics(PHYS_Spider);
				P.NHeroEffect.SetBase(P);
				
			
			}
			
			
			if (A.CreatureID == -1)
			{
				P = Pawn(A);
				testVector = GetPlayerPosition();
				testVector.X = testVector.X + 333;
				AddSystemMessageString("ScanActor: ETO CONTROLLER");//bSetSizeScale
				C = LineagePlayerController(A);
				AddSystemMessageString("ScanActor:" @ "C.Pawn.Name" @ C.Pawn.Name);
				AddSystemMessageString("ScanActor:" @ "C.Pawn.Tag" @ C.Pawn.Tag);
				AddSystemMessageString("ScanActor:" @ "C.Pawn.CreatureID" @ C.Pawn.CreatureID);
				//C.MoveTo(testVector);
				//C.ClientSetLocation(testVector, defaultRotion);
			//	C.ReplicateMove(1.0f, testVector, DCLICK_None, defaultRotion);
				C.ProcessMove(2.0f, testVector, DCLICK_None, defaultRotion);
			}
	}
}*/

function OnShow()
{
	// Forca a janela principal do Radar e seus sub-paineis a aparecerem
	wnd_RadarMap.ShowWindow();
	wnd_RadarMapRotation.ShowWindow();
	rdr_RadarMapTex.ShowWindow();
	rdr_RadarMapObject.ShowWindow();
	
	// Trava o foco da janela para ela vir para a frente de tudo
	wnd_RadarMap.SetFocus();
}



defaultproperties
{
    
}
