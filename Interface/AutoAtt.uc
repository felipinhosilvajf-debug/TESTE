class AutoAtt extends UICommonAPI;

const TIMER_ATT = 2288;

var WindowHandle Me;

var ButtonHandle btnInsert;
var ButtonHandle btnContinue;
var ButtonHandle btnWeapon;
var ButtonHandle btnArmor;
var ButtonHandle btnBack;
var ButtonHandle btnStop;

var ItemWindowHandle itemMainWeapon;
var ItemWindowHandle itemMainArmor;

var ItemWindowHandle itemMainStone;
var ItemWindowHandle itemMainStone2;
var ItemWindowHandle itemMainStone3;
var ItemWindowHandle itemMainCrystal;
var ItemWindowHandle itemMainCrystal2;
var ItemWindowHandle itemMainCrystal3;

var ItemWindowHandle itemFire;
var ItemWindowHandle itemWater;
var ItemWindowHandle itemEarth;
var ItemWindowHandle itemWind;
var ItemWindowHandle itemHoly;
var ItemWindowHandle itemDark;

var ItemWindowHandle itemFireCrystal;
var ItemWindowHandle itemWaterCrystal;
var ItemWindowHandle itemEarthCrystal;
var ItemWindowHandle itemWindCrystal;
var ItemWindowHandle itemHolyCrystal;
var ItemWindowHandle itemDarkCrystal;

var ItemWindowHandle ihandle;

var ItemInfo mainInfo;

var ItemInfo stoneInfo;
var ItemInfo stoneInfo2;
var ItemInfo stoneInfo3;
var ItemInfo crystalInfo;
var ItemInfo crystalInfo2;
var ItemInfo crystalInfo3;

var ItemID mainID;

var ItemInfo fireStoneInfo;
var ItemInfo waterStoneInfo;
var ItemInfo earthStoneInfo;
var ItemInfo windStoneInfo;
var ItemInfo holyStoneInfo;
var ItemInfo darkStoneInfo;

var ItemInfo fireCrystalInfo;
var ItemInfo waterCrystalInfo;
var ItemInfo earthCrystalInfo;
var ItemInfo windCrystalInfo;
var ItemInfo holyCrystalInfo;
var ItemInfo darkCrystalInfo;

var TextBoxHandle txtStoneCount;
var TextBoxHandle txtStoneCount2;
var TextBoxHandle txtStoneCount3;
var TextBoxHandle txtCrystalCount;
var TextBoxHandle txtCrystalCount2;
var TextBoxHandle txtCrystalCount3;

var TextBoxHandle txtFireCount;
var TextBoxHandle txtWaterCount;
var TextBoxHandle txtEarthCount;
var TextBoxHandle txtWindCount;
var TextBoxHandle txtDarkCount;
var TextBoxHandle txtHolyCount;

var TextBoxHandle txtFireCrystal;
var TextBoxHandle txtWaterCrystal;
var TextBoxHandle txtEarthCrystal;
var TextBoxHandle txtWindCrystal;
var TextBoxHandle txtDarkCrystal;
var TextBoxHandle txtHolyCrystal;

var TextBoxHandle txtProgress1;
var TextBoxHandle txtProgress2;
var TextBoxHandle txtProgress3;

var TextBoxHandle txtGuide;

var TextBoxHandle txtDescFire;
var TextBoxHandle txtDescWater;
var TextBoxHandle txtDescEarth;
var TextBoxHandle txtDescWind;
var TextBoxHandle txtDescDark;
var TextBoxHandle txtDescHoly;

var BarHandle attProgress1;
var BarHandle attProgress2;
var BarHandle attProgress3;

var TextureHandle bgMainItem;
var TextureHandle bgMainChest;

var TextureHandle bgMainStone;
var TextureHandle bgMainStone2;
var TextureHandle bgMainStone3;
var TextureHandle bgMainCrystal;
var TextureHandle bgMainCrystal2;
var TextureHandle bgMainCrystal3;

var TextureHandle bgFireStone;
var TextureHandle bgWaterStone;
var TextureHandle bgEarthStone;
var TextureHandle bgWindStone;
var TextureHandle bgDarkStone;
var TextureHandle bgHolyStone;

var TextureHandle bgFireCrystal;
var TextureHandle bgWaterCrystal;
var TextureHandle bgEarthCrystal;
var TextureHandle bgWindCrystal;
var TextureHandle bgDarkCrystal;
var TextureHandle bgHolyCrystal;

var TextureHandle bgMain;

var TextureHandle texHL1;
var TextureHandle texHL2;

var SliderCtrlHandle sliderSpeed;

var int curAttValue;
var int curArmorAttValue;
var int curArmorAttValue2;
var int curArmorAttValue3;

var int curStoneCount;
var int curStoneCount2;
var int curStoneCount3;
var int curCrystalCount;
var int curCrystalCount2;
var int curCrystalCount3;

var int weapEnchantOption;
var int armorEnchantOption;
var int armorEnchantOption2;
var int armorEnchantOption3;

var int AttType[6]; //0 - Fire, 1 - Water, 2 - Earth, 3 - Wind, 4 - Dark, 5 - Holy

var int EnSpeed;

var bool isWeapon;

var bool isMainStoneChosen;
var bool isMainStoneChosen2;
var bool isMainStoneChosen3;

var bool isMainCrystalChosen;
var bool isMainCrystalChosen2;
var bool isMainCrystalChosen3;

var bool isFireStoneChosen;
var bool isWaterStoneChosen;
var bool isEarthStoneChosen;
var bool isWindStoneChosen;
var bool isDarkStoneChosen;
var bool isHolyStoneChosen;

var bool flagFirstStop;
var bool flagSecondStop;
var bool flagThirdStop;

var string mainStoneType;
var string mainStoneType2;
var string mainStoneType3;

var string mainCrystalType;
var string mainCrystalType2;
var string mainCrystalType3;

var string armorFirstAtt;
var string armorSecondAtt;
var string armorThirdAtt;

function OnRegisterEvent()
{
	RegisterEvent(EV_GamingStateEnter);
	RegisterEvent(EV_AttributeEnchantItemShow);
	RegisterEvent(EV_AttributeEnchantResult);
	RegisterEvent(EV_InventoryUpdateItem);
	RegisterEvent(EV_SystemMessage);
}

function OnEvent(int EventID, string param)
{
	//local int updatedValue;
	local int msgID;
	
	if ( Me.IsShowWindow() )
	{
		switch(EventID)
		{
			case EV_AttributeEnchantItemShow:
				//sysDebug("EVENT SHOW");
				EnchantItem(param);
			break;
			case EV_AttributeEnchantResult:
				//ParseInt(param, "Result", updatedValue);
				//sysDebug("EVENT RESULT -" @ string(updatedValue));
				UpdateMainItemInfo();
				
				if (isWeapon)
				{
					curAttValue = mainInfo.AttackAttributeValue;
					//sysDebug("RESULT -" @ string( curAttValue ));
				}
				else
				{
					curAttValue = GetDynamicArmorAttValue();
					//sysDebug("RESULT -" @ string( GetDynamicArmorAttValue() ));
				}
				
				//curAttValue += updatedValue;
				if (isWeapon)
				{
					if (curAttValue < 150)
					{
						attProgress1.SetValue(150, curAttValue);
					}
					else if (curAttValue >= 150)
					{
						attProgress1.SetValue(300, curAttValue);
					}
					txtProgress1.SetText(string(curAttValue));
				}
				else
				{
					if (!flagFirstStop)
					{
						if (curAttValue < 60)
						{
							attProgress1.SetValue(60, curAttValue);
						}
						else if (curAttValue >= 60)
						{
							attProgress1.SetValue(120, curAttValue);
						}
						txtProgress1.SetText(string(curAttValue));
					} else if (!flagSecondStop)
					{
						if (curAttValue < 60)
						{
							attProgress2.SetValue(60, curAttValue);
						}
						else if (curAttValue >= 60)
						{
							attProgress2.SetValue(120, curAttValue);
						}
						txtProgress2.SetText(string(curAttValue));
					} else if (!flagThirdStop)
					{
						if (curAttValue < 60)
						{
							attProgress3.SetValue(60, curAttValue);
						}
						else if (curAttValue >= 60)
						{
							attProgress3.SetValue(120, curAttValue);
						}
						txtProgress3.SetText(string(curAttValue));
					}
				}
				break;
			case EV_InventoryUpdateItem:
				UpdateAttInfo();
			break;
			case EV_SystemMessage:
				ParseInt(param, "Index", msgID);
				if (msgID == 2149)
				{
					if (isWeapon)
					{
						curStoneCount = CalcStonesAtt(stoneInfo.ID.ClassID, "");
						curCrystalCount = CalcCrystalsAtt(crystalInfo.ID.ClassID, "");
						//sysDebug("Stone:" @ curStoneCount @ "Crystal:" @ curCrystalCount);
					}
					else
					{
						curStoneCount = CalcStonesAtt(stoneInfo.ID.ClassID, "");
						curCrystalCount = CalcCrystalsAtt(crystalInfo.ID.ClassID, "");
						curStoneCount2 = CalcStonesAtt(stoneInfo2.ID.ClassID, "");
						curStoneCount3 = CalcStonesAtt(stoneInfo3.ID.ClassID, "");
						curCrystalCount2 = CalcCrystalsAtt(crystalInfo2.ID.ClassID, "");
						curCrystalCount3 = CalcCrystalsAtt(crystalInfo3.ID.ClassID, "");
						//sysDebug("Stone:" @ curStoneCount @ "Crystal:" @ curCrystalCount);
						//sysDebug("Stone2:" @ curStoneCount2 @ "Crystal2:" @ curCrystalCount2);
						//sysDebug("Stone3:" @ curStoneCount3 @ "Crystal3:" @ curCrystalCount3);
					}
				}
				else if (msgID == 2147 || msgID == 2148)
				{
					curStoneCount = CalcStonesAtt(stoneInfo.ID.ClassID, "");
					curCrystalCount = CalcCrystalsAtt(crystalInfo.ID.ClassID, "");
					//sysDebug("Stone:" @ curStoneCount @ "Crystal:" @ curCrystalCount);
				}
				else if (msgID == 3144 || msgID == 3163)
				{
					curStoneCount = CalcStonesAtt(stoneInfo.ID.ClassID, "");
					curCrystalCount = CalcCrystalsAtt(crystalInfo.ID.ClassID, "");
					curStoneCount2 = CalcStonesAtt(stoneInfo2.ID.ClassID, "");
					curStoneCount3 = CalcStonesAtt(stoneInfo3.ID.ClassID, "");
					curCrystalCount2 = CalcCrystalsAtt(crystalInfo2.ID.ClassID, "");
					curCrystalCount3 = CalcCrystalsAtt(crystalInfo3.ID.ClassID, "");
					//sysDebug("Stone:" @ curStoneCount @ "Crystal:" @ curCrystalCount);
					//sysDebug("Stone2:" @ curStoneCount2 @ "Crystal2:" @ curCrystalCount2);
					//sysDebug("Stone3:" @ curStoneCount3 @ "Crystal3:" @ curCrystalCount3);
				}
			break;
		}
	}
}

function UpdateMainItemInfo()
{
	if (!ihandle.GetItem(ihandle.FindItem(mainInfo.ID), mainInfo))
	{
		MessageBox("Cant find item in inventory");
	}
}

function UpdateAttInfo()
{
	local ItemID tempID;
	tempID.ClassID = 9546;
	ihandle.GetItem(ihandle.FindItem(tempID), fireStoneInfo);
	tempID.ClassID = 9547;
	ihandle.GetItem(ihandle.FindItem(tempID), waterStoneInfo);
	tempID.ClassID = 9548;
	ihandle.GetItem(ihandle.FindItem(tempID), earthStoneInfo);
	tempID.ClassID = 9549;
	ihandle.GetItem(ihandle.FindItem(tempID), windStoneInfo);
	tempID.ClassID = 9550;
	ihandle.GetItem(ihandle.FindItem(tempID), darkStoneInfo);
	tempID.ClassID = 9551;
	ihandle.GetItem(ihandle.FindItem(tempID), holyStoneInfo);
	tempID.ClassID = 9552;
	ihandle.GetItem(ihandle.FindItem(tempID), fireCrystalInfo);
	tempID.ClassID = 9553;
	ihandle.GetItem(ihandle.FindItem(tempID), waterCrystalInfo);
	tempID.ClassID = 9554;
	ihandle.GetItem(ihandle.FindItem(tempID), earthCrystalInfo);
	tempID.ClassID = 9555;
	ihandle.GetItem(ihandle.FindItem(tempID), windCrystalInfo);
	tempID.ClassID = 9556;
	ihandle.GetItem(ihandle.FindItem(tempID), darkCrystalInfo);
	tempID.ClassID = 9557;
	ihandle.GetItem(ihandle.FindItem(tempID), holyCrystalInfo);
}

function OnLoad()
{
	Me = GetWindowHandle("AutoAtt");
	
	//ItemWindows
	itemMainWeapon = GetItemWindowHandle("AutoAtt.itemMainWeapon");		
	itemMainArmor = GetItemWindowHandle("AutoAtt.itemMainArmor");
	
	itemMainStone = GetItemWindowHandle("AutoAtt.itemMainStone");
	itemMainStone2 = GetItemWindowHandle("AutoAtt.itemMainStone2");
	itemMainStone3 = GetItemWindowHandle("AutoAtt.itemMainStone3");
	
	itemMainCrystal = GetItemWindowHandle("AutoAtt.itemMainCrystal");
	itemMainCrystal2 = GetItemWindowHandle("AutoAtt.itemMainCrystal2");
	itemMainCrystal3 = GetItemWindowHandle("AutoAtt.itemMainCrystal3");
	
	itemFire = GetItemWindowHandle("AutoAtt.itemFire");
	itemWater = GetItemWindowHandle("AutoAtt.itemWater");
	itemEarth = GetItemWindowHandle("AutoAtt.itemEarth");
	itemWind = GetItemWindowHandle("AutoAtt.itemWind");
	itemDark = GetItemWindowHandle("AutoAtt.itemDark");
	itemHoly = GetItemWindowHandle("AutoAtt.itemHoly");
	
	itemFireCrystal = GetItemWindowHandle("AutoAtt.itemFireCrystal");
	itemWaterCrystal = GetItemWindowHandle("AutoAtt.itemWaterCrystal");
	itemEarthCrystal = GetItemWindowHandle("AutoAtt.itemEarthCrystal");
	itemWindCrystal = GetItemWindowHandle("AutoAtt.itemWindCrystal");
	itemDarkCrystal = GetItemWindowHandle("AutoAtt.itemDarkCrystal");
	itemHolyCrystal = GetItemWindowHandle("AutoAtt.itemHolyCrystal");
	
	ihandle = GetItemWindowHandle("InventoryWnd.InventoryItem");
	
	//InvItem = GetItemWindowHandle( "InventoryWnd.InventoryItem" );
	
	//Buttons
	//bExpand = GetButtonHandle("AutoPotions.expandBtn");
	//bExpandMore = GetButtonHandle("AutoPotions.expandMoreBtn");
	
	//Textures
	bgMainItem = GetTextureHandle("AutoPotions.texMain");
	bgMainChest = GetTextureHandle("AutoPotions.bgChest");
	
	bgMainStone = GetTextureHandle("AutoPotions.bgMainStone");
	bgMainStone2 = GetTextureHandle("AutoPotions.bgMainStone2");
	bgMainStone3 = GetTextureHandle("AutoPotions.bgMainStone3");
	
	bgMainCrystal = GetTextureHandle("AutoPotions.bgMainCrystal");
	bgMainCrystal2 = GetTextureHandle("AutoPotions.bgMainCrystal2");
	bgMainCrystal3 = GetTextureHandle("AutoPotions.bgMainCrystal3");
	
	bgFireStone = GetTextureHandle("AutoPotions.bgFireStone");
	bgWaterStone = GetTextureHandle("AutoPotions.bgWaterStone");
	bgEarthStone = GetTextureHandle("AutoPotions.bgEarthStone");
	bgWindStone = GetTextureHandle("AutoPotions.bgWindStone");
	bgDarkStone = GetTextureHandle("AutoPotions.bgDarkStone");
	bgHolyStone = GetTextureHandle("AutoPotions.bgHolyStone");
	
	bgFireCrystal = GetTextureHandle("AutoPotions.bgFireCrystal");
	bgWaterCrystal = GetTextureHandle("AutoPotions.bgWaterCrystal");
	bgEarthCrystal = GetTextureHandle("AutoPotions.bgEarthCrystal");
	bgWindCrystal = GetTextureHandle("AutoPotions.bgWindCrystal");
	bgDarkCrystal = GetTextureHandle("AutoPotions.bgDarkCrystal");
	bgHolyCrystal = GetTextureHandle("AutoPotions.bgHolyCrystal");
	
	bgMain = GetTextureHandle("AutoPotions.texBG");
	
	texHL1 = GetTextureHandle("AutoPotions.texHL1");
	texHL2 = GetTextureHandle("AutoPotions.texHL2");
	
	//TextBoxes
	txtStoneCount = GetTextBoxHandle("AutoAtt.txtStoneCount");
	txtStoneCount2 = GetTextBoxHandle("AutoAtt.txtStoneCount2");
	txtStoneCount3 = GetTextBoxHandle("AutoAtt.txtStoneCount3");
	
	txtCrystalCount = GetTextBoxHandle("AutoAtt.txtCrystalCount");
	txtCrystalCount2 = GetTextBoxHandle("AutoAtt.txtCrystalCount2");
	txtCrystalCount3 = GetTextBoxHandle("AutoAtt.txtCrystalCount3");
	
	txtFireCount = GetTextBoxHandle("AutoAtt.txtFireCount");
	txtWaterCount = GetTextBoxHandle("AutoAtt.txtWaterCount");
	txtEarthCount = GetTextBoxHandle("AutoAtt.txtEarthCount");
	txtWindCount = GetTextBoxHandle("AutoAtt.txtWindCount");
	txtDarkCount = GetTextBoxHandle("AutoAtt.txtDarkCount");
	txtHolyCount = GetTextBoxHandle("AutoAtt.txtHolyCount");
	
	txtFireCrystal = GetTextBoxHandle("AutoAtt.txtFireCrystal");
	txtWaterCrystal = GetTextBoxHandle("AutoAtt.txtWaterCrystal");
	txtEarthCrystal = GetTextBoxHandle("AutoAtt.txtEarthCrystal");
	txtWindCrystal = GetTextBoxHandle("AutoAtt.txtWindCrystal");
	txtDarkCrystal = GetTextBoxHandle("AutoAtt.txtDarkCrystal");
	txtHolyCrystal = GetTextBoxHandle("AutoAtt.txtHolyCrystal");
	
	txtProgress1 = GetTextBoxHandle("AutoAtt.txtProgress1");
	txtProgress2 = GetTextBoxHandle("AutoAtt.txtProgress2");
	txtProgress3 = GetTextBoxHandle("AutoAtt.txtProgress3");
	
	txtGuide = GetTextBoxHandle("AutoAtt.txtGuide");
	
	txtDescFire = GetTextBoxHandle("AutoAtt.txtDescFire");
	txtDescWater = GetTextBoxHandle("AutoAtt.txtDescWater");
	txtDescEarth = GetTextBoxHandle("AutoAtt.txtDescEarth");
	txtDescWind = GetTextBoxHandle("AutoAtt.txtDescWind");
	txtDescDark = GetTextBoxHandle("AutoAtt.txtDescDark");
	txtDescHoly = GetTextBoxHandle("AutoAtt.txtDescHoly");
	
	//BarHandle
	attProgress1 = GetBarHandle("AutoAtt.attProgress1");
	attProgress2 = GetBarHandle("AutoAtt.attProgress2");
	attProgress3 = GetBarHandle("AutoAtt.attProgress3");
	
	//ButtonHandle
	btnContinue = GetButtonHandle("AutoAtt.btnContinue");
	btnInsert = GetButtonHandle("AutoAtt.btnInsert");
	btnWeapon = GetButtonHandle("AutoAtt.btnWeapon");
	btnArmor = GetButtonHandle("AutoAtt.btnArmor");
	btnBack = GetButtonHandle("AutoAtt.btnBack");
	btnStop = GetButtonHandle("AutoAtt.btnStop");
	
	sliderSpeed = GetSliderCtrlHandle("AutoAtt.sliderSpeed");
	
	//Load settings
	EnSpeed = 500;
	txtGuide.SetText(string(EnSpeed) @ "ms");
	SetToDefault();
}

function SetToDefault()
{
	Me.SetWindowSize(256, 168);
	attProgress1.SetWindowSize(86, 7);
	bgMain.SetWindowSize(245, 126);
	bgMain.SetAnchor("AutoAtt", "TopLeft", "TopLeft", 5, 35);
	btnWeapon.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 48);
	btnArmor.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 80);
	
	txtGuide.SetAnchor("AutoAtt", "BottomCenter", "BottomCenter", 0, -38);
	txtGuide.SetText(string(EnSpeed) @ "ms");
	
	txtDescFire.SetAnchor("AutoAtt", "TopCenter", "TopCenter", -12, 106);
	txtDescEarth.SetAnchor("AutoAtt", "TopCenter", "TopCenter", -12, 146);
	txtDescDark.SetAnchor("AutoAtt", "TopCenter", "TopCenter", -12, 186);
	
	txtDescWater.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 12, 120);
	txtDescWind.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 12, 160);
	txtDescHoly.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 12, 200);
	
	texHL1.HideWindow();
	texHL2.HideWindow();
	sliderSpeed.ShowWindow();
	
	curAttValue = 0;
	curArmorAttValue = 0;
	curArmorAttValue2 = 0;
	curArmorAttValue3 = 0;
	
	weapEnchantOption = -1;
	armorEnchantOption = -1;
	armorEnchantOption2 = -1;
	armorEnchantOption3 = -1;
	
	curStoneCount = 0;
	curStoneCount2 = 0;
	curStoneCount3 = 0;
	curCrystalCount = 0;
	curCrystalCount2 = 0;
	curCrystalCount3 = 0;
	
	isWeapon = false;
	
	btnInsert.HideWindow();
	btnContinue.HideWindow();
	btnBack.HideWindow();
	btnStop.HideWindow();
	
	isMainStoneChosen = false;
	isMainStoneChosen2 = false;
	isMainStoneChosen3 = false;
	
	isMainCrystalChosen = false;
	isMainCrystalChosen2 = false;
	isMainCrystalChosen3 = false;
	
	isFireStoneChosen = false;
	isWaterStoneChosen = false;
	isEarthStoneChosen = false;
	isWindStoneChosen = false;
	isDarkStoneChosen = false;
	isHolyStoneChosen = false;
	
	flagFirstStop = false;
	flagSecondStop = false;
	flagThirdStop = false;
	
	txtStoneCount.SetText("");
	txtStoneCount2.SetText("");
	txtStoneCount3.SetText("");
	
	txtCrystalCount.SetText("");
	txtCrystalCount2.SetText("");
	txtCrystalCount3.SetText("");
	
	mainCrystalType = "";
	mainCrystalType2 = "";
	mainCrystalType3 = "";
	
	mainStoneType = "";
	mainStoneType2 = "";
	mainStoneType3 = "";
	
	armorFirstAtt = "";
	armorSecondAtt = "";
	armorThirdAtt = "";
	
	btnWeapon.ShowWindow();
	btnArmor.ShowWindow();
	
	itemMainWeapon.HideWindow();
	itemMainArmor.HideWindow();
	
	itemMainStone.HideWindow();
	itemMainStone2.HideWindow();
	itemMainStone3.HideWindow();
	
	itemMainCrystal.HideWindow();
	itemMainCrystal2.HideWindow();
	itemMainCrystal3.HideWindow();
	
	bgMainItem.HideWindow();
	bgMainChest.HideWindow();
	
	bgMainStone.HideWindow();
	bgMainStone2.HideWindow();
	bgMainStone3.HideWindow();
	
	bgMainCrystal.HideWindow();
	bgMainCrystal2.HideWindow();
	bgMainCrystal3.HideWindow();
	
	itemFire.HideWindow();
	itemWater.HideWindow();
	itemEarth.HideWindow();
	itemWind.HideWindow();
	itemHoly.HideWindow();
	itemDark.HideWindow();
	
	itemFireCrystal.HideWindow();
	itemWaterCrystal.HideWindow();
	itemEarthCrystal.HideWindow();
	itemWindCrystal.HideWindow();
	itemHolyCrystal.HideWindow();
	itemDarkCrystal.HideWindow();
	
	bgFireStone.HideWindow();
	bgWaterStone.HideWindow();
	bgEarthStone.HideWindow();
	bgWindStone.HideWindow();
	bgDarkStone.HideWindow();
	bgHolyStone.HideWindow();
	
	bgFireCrystal.HideWindow();
	bgWaterCrystal.HideWindow();
	bgEarthCrystal.HideWindow();
	bgWindCrystal.HideWindow();
	bgDarkCrystal.HideWindow();
	bgHolyCrystal.HideWindow();
	
	txtFireCount.HideWindow();
	txtWaterCount.HideWindow();
	txtEarthCount.HideWindow();
	txtWindCount.HideWindow();
	txtDarkCount.HideWindow();
	txtHolyCount.HideWindow();
	
	txtFireCrystal.HideWindow();
	txtWaterCrystal.HideWindow();
	txtEarthCrystal.HideWindow();
	txtWindCrystal.HideWindow();
	txtDarkCrystal.HideWindow();
	txtHolyCrystal.HideWindow();
	
	txtDescFire.HideWindow();
	txtDescWater.HideWindow();
	txtDescEarth.HideWindow();
	txtDescWind.HideWindow();
	txtDescDark.HideWindow();
	txtDescHoly.HideWindow();
	
	attProgress1.HideWindow();
	attProgress1.Clear();
	attProgress2.HideWindow();
	attProgress2.Clear();
	attProgress3.HideWindow();
	attProgress3.Clear();
	
	itemMainStone.DisableWindow();
	itemMainStone2.DisableWindow();
	itemMainStone3.DisableWindow();
	itemMainCrystal.DisableWindow();
	itemMainCrystal2.DisableWindow();
	itemMainCrystal3.DisableWindow();
	
	itemMainWeapon.EnableWindow();
	itemMainArmor.EnableWindow();
	
	txtProgress1.HideWindow();
	txtProgress1.SetText("0");
	txtProgress2.HideWindow();
	txtProgress2.SetText("0");
	txtProgress3.HideWindow();
	txtProgress3.SetText("0");
	
	itemFire.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -92, 36);
	itemEarth.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -56, 36);
	itemDark.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 36);

	itemWater.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 36);
	itemWind.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 56, 36);
	itemHoly.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 92, 36);
	
	itemFireCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -92, 74);
	itemEarthCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -56, 74);
	itemDarkCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 74);

	itemWaterCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 74);
	itemWindCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 56, 74);
	itemHolyCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 92, 74);
	
}

function ResetVariables(string type)
{
	if (type == "Weapon")
	{
		curAttValue = 0;
		curStoneCount = 0;
		curCrystalCount = 0;
		weapEnchantOption = -1;
		mainCrystalType = "";
		mainStoneType = "";
		isMainStoneChosen = false;
		isMainCrystalChosen = false;
		isFireStoneChosen = false;
		isWaterStoneChosen = false;
		isEarthStoneChosen = false;
		isWindStoneChosen = false;
		isDarkStoneChosen = false;
		isHolyStoneChosen = false;
		txtStoneCount.SetText("");
		txtCrystalCount.SetText("");
		mainID = GetItemID(-1);
		itemMainWeapon.Clear();
		itemMainStone.Clear();
		itemMainCrystal.Clear();
		bgMainStone.ShowWindow();
		bgMainCrystal.ShowWindow();
	}
	else if (type == "Armor")
	{
		curAttValue = 0;
		curArmorAttValue = 0;
		curArmorAttValue2 = 0;
		curArmorAttValue3 = 0;
		armorEnchantOption = -1;
		armorEnchantOption2 = -1;
		armorEnchantOption3 = -1;
		curStoneCount = 0;
		curStoneCount2 = 0;
		curStoneCount3 = 0;
		curCrystalCount = 0;
		curCrystalCount2 = 0;
		curCrystalCount3 = 0;
		isMainStoneChosen = false;
		isMainStoneChosen2 = false;
		isMainStoneChosen3 = false;
		isMainCrystalChosen = false;
		isMainCrystalChosen2 = false;
		isMainCrystalChosen3 = false;
		isFireStoneChosen = false;
		isWaterStoneChosen = false;
		isEarthStoneChosen = false;
		isWindStoneChosen = false;
		isDarkStoneChosen = false;
		isHolyStoneChosen = false;
		flagFirstStop = false;
		flagSecondStop = false;
		flagThirdStop = false;
		txtStoneCount.SetText("");
		txtStoneCount2.SetText("");
		txtStoneCount3.SetText("");
		txtCrystalCount.SetText("");
		txtCrystalCount2.SetText("");
		txtCrystalCount3.SetText("");
		mainCrystalType = "";
		mainCrystalType2 = "";
		mainCrystalType3 = "";
		mainStoneType = "";
		mainStoneType2 = "";
		mainStoneType3 = "";
		armorFirstAtt = "";
		armorSecondAtt = "";
		armorThirdAtt = "";
		mainID = GetItemID(-1);
		itemMainStone.Clear();
		itemMainStone2.Clear();
		itemMainStone3.Clear();
		itemMainCrystal.Clear();
		itemMainCrystal2.Clear();
		itemMainCrystal3.Clear();
		itemMainArmor.Clear();
		bgMainStone.ShowWindow();
		bgMainStone2.ShowWindow();
		bgMainStone3.ShowWindow();
		bgMainCrystal.ShowWindow();
		bgMainCrystal2.ShowWindow();
		bgMainCrystal3.ShowWindow();
	}
}

function OnTimer(int TimerID)
{
	local ItemID remID;
	
	remID = GetItemID(-1);
	
	if (TimerID == TIMER_ATT)
	{
		Me.KillTimer(TIMER_ATT);
		
		//sysDebug("ON TIMER KILL");
		
		if (isMainStoneChosen)
		{
			txtStoneCount.SetText( string( curStoneCount ) );
		}
		else
		{
			txtStoneCount.SetText("");
		}
		
		if (isMainCrystalChosen)
		{
			txtCrystalCount.SetText( string( curCrystalCount ) );
		}
		else
		{
			txtCrystalCount.SetText("");
		}
		
		if (isWeapon)
		{
			class'EnchantAPI'.static.RequestEnchantItemAttribute(remID);
			OnClickInsert();
		}
		else
		{
			if (isMainStoneChosen2)
			{
				txtStoneCount2.SetText( string( curStoneCount2 ) );
			}
			else
			{
				txtStoneCount2.SetText("");
			}
			
			if (isMainCrystalChosen2)
			{
				txtCrystalCount2.SetText( string( curCrystalCount2 ) );
			}
			else
			{
				txtCrystalCount2.SetText("");
			}
			
			if (isMainStoneChosen3)
			{
				txtStoneCount3.SetText( string( curStoneCount3 ) );
			}
			else
			{
				txtStoneCount3.SetText("");
			}
			
			if (isMainCrystalChosen3)
			{
				txtCrystalCount3.SetText( string( curCrystalCount3 ) );
			}
			else
			{
				txtCrystalCount3.SetText("");
			}
			
			//sysDebug("ON TIMER NEXT");
			class'EnchantAPI'.static.RequestEnchantItemAttribute(remID);
			OnClickInsert();
		}
	}
}

function EnchantItem(string param)
{
	local int count;
	local ItemID cID;
	
	ParseItemID(param, cID);
	
	count = 0;
	
	switch(cID.ClassID)
	{
		case 9546:
		case 9547:
		case 9548:
		case 9549:
		case 9550:
		case 9551:
			if (isWeapon)
			{
				count = curStoneCount;
			} else
			{
				if (curStoneCount != 0)
				{
					count = curStoneCount;
				} else if (curStoneCount2 != 0)
				{
					count = curStoneCount2;
				} else if (curStoneCount3 != 0)
				{
					count = curStoneCount3;
				}
			}
			ihandle.GetItem(ihandle.FindItem(mainID), mainInfo);
		break;
		case 9552:
		case 9553:
		case 9554:
		case 9555:
		case 9556:
		case 9557:
			if (isWeapon)
			{
				count = curCrystalCount;
			} else
			{
				if (curCrystalCount != 0)
				{
					count = curCrystalCount;
				} else if (curCrystalCount2 != 0)
				{
					count = curCrystalCount2;
				} else if (curCrystalCount3 != 0)
				{
					count = curCrystalCount3;
				}
			}
			ihandle.GetItem(ihandle.FindItem(mainID), mainInfo);
		break;
	}
	
	if (count != 0)
	{
		class'EnchantAPI'.static.RequestEnchantItemAttribute(mainInfo.ID);
		Me.SetTimer(TIMER_ATT, EnSpeed);
		//sysDebug("count > 0");
	}
	else
	{
		Me.KillTimer(TIMER_ATT);
		//sysDebug("count = 0");
		MessageBox("Not enough stones/crystals!");
	}
}

function OnClickButton(string strID)
{
	switch(strID)
	{
		case "btnInsert":
			OnClickInsert();
			btnStop.ShowWindow();
		break;
		case "btnContinue":
			OnClickContinue();
		break;
		case "btnWeapon":
			OnClickWeapon();
			isWeapon = true;
		break;
		case "btnArmor":
			OnClickArmor();
			isWeapon = false;
		break;
		case "btnBack":
			ClearItem();
		break;
		case "btnStop":
			Me.KillTimer(TIMER_ATT);
			btnInsert.ShowWindow();
			btnStop.HideWindow();
		break;
	}
}

function OnClickWeapon()
{
	Me.SetWindowSize(256, 196);
	attProgress1.SetWindowSize(108, 7);
	bgMain.SetWindowSize(245, 98);
	bgMain.SetAnchor("AutoAtt", "TopLeft", "TopLeft", 5, 54);
	txtGuide.SetAnchor("AutoAtt", "BottomCenter", "BottomCenter", 0, -54);
	
	txtGuide.SetText("Place the item to be enchanted.");
	
	sliderSpeed.HideWindow();
	
	btnWeapon.HideWindow();
	btnArmor.HideWindow();
	btnBack.ShowWindow();
	
	texHL1.ShowWindow();
	texHL1.SetAnchor("AutoAtt.itemMainWeapon", "TopLeft", "TopLeft", 0, -10);
	
	itemMainWeapon.SetAnchor("AutoAtt", "TopCenter", "TopCenter", -36, 86);
	
	itemMainStone.SetAnchor("AutoAtt.itemMainWeapon", "TopLeft", "TopLeft", 40, 0);
	itemMainCrystal.SetAnchor("AutoAtt.itemMainStone", "TopLeft", "TopLeft", 40, 0);
	
	itemMainStone.SetTooltipCustomType(MakeTooltipSimpleText(""));
	itemMainStone2.SetTooltipCustomType(MakeTooltipSimpleText(""));
	itemMainStone3.SetTooltipCustomType(MakeTooltipSimpleText(""));
	
	itemMainCrystal.SetTooltipCustomType(MakeTooltipSimpleText(""));
	itemMainCrystal2.SetTooltipCustomType(MakeTooltipSimpleText(""));
	itemMainCrystal3.SetTooltipCustomType(MakeTooltipSimpleText(""));
	
	bgMainItem.ShowWindow();
	bgMainStone.ShowWindow();
	bgMainCrystal.ShowWindow();
	
	itemMainWeapon.ShowWindow();
	itemMainStone.ShowWindow();
	itemMainCrystal.ShowWindow();
	
	
}

function OnClickArmor()
{
	Me.SetWindowSize(256, 288);
	attProgress1.SetWindowSize(86, 7);
	bgMain.SetWindowSize(245, 208);
	bgMain.SetAnchor("AutoAtt", "TopLeft", "TopLeft", 5, 40);
	txtGuide.SetAnchor("AutoAtt", "BottomCenter", "BottomCenter", 0, -48);
	
	txtGuide.SetText("Place the item to be enchanted.");
	
	sliderSpeed.HideWindow();
	
	btnWeapon.HideWindow();
	btnArmor.HideWindow();
	btnBack.ShowWindow();
	
	texHL1.ShowWindow();
	texHL1.SetAnchor("AutoAtt.itemMainArmor", "TopLeft", "TopLeft", 0, -10);
	
	itemMainArmor.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 58);
	
	itemMainStone.SetAnchor("AutoAtt.itemMainArmor", "BottomCenter", "BottomCenter", -56, 44);
	itemMainCrystal.SetAnchor("AutoAtt.itemMainStone", "TopLeft", "TopLeft", 112, 0);
	
	itemMainStone.SetTooltipCustomType(MakeTooltipSimpleText("Fire / Water"));
	itemMainStone2.SetTooltipCustomType(MakeTooltipSimpleText("Earth / Wind"));
	itemMainStone3.SetTooltipCustomType(MakeTooltipSimpleText("Dark / Holy"));
	
	itemMainCrystal.SetTooltipCustomType(MakeTooltipSimpleText("Fire / Water"));
	itemMainCrystal2.SetTooltipCustomType(MakeTooltipSimpleText("Earth / Wind"));
	itemMainCrystal3.SetTooltipCustomType(MakeTooltipSimpleText("Dark / Holy"));
	
	bgMainChest.ShowWindow();
	bgMainStone.ShowWindow();
	bgMainStone2.ShowWindow();
	bgMainStone3.ShowWindow();
	bgMainCrystal.ShowWindow();
	bgMainCrystal2.ShowWindow();
	bgMainCrystal3.ShowWindow();
	
	itemMainArmor.ShowWindow();
	itemMainStone.ShowWindow();
	itemMainStone2.ShowWindow();
	itemMainStone3.ShowWindow();
	itemMainCrystal.ShowWindow();
	itemMainCrystal2.ShowWindow();
	itemMainCrystal3.ShowWindow();
	
	txtDescFire.ShowWindow();
	txtDescWater.ShowWindow();
	txtDescEarth.ShowWindow();
	txtDescWind.ShowWindow();
	txtDescDark.ShowWindow();
	txtDescHoly.ShowWindow();
	
}

function OnClickInsert()
{
	btnInsert.HideWindow();
	
	if (isWeapon)
	{
		curAttValue = mainInfo.AttackAttributeValue;
		//sysDebug("Current att: " $ string(curAttValue));
		
		if (curAttValue == 300)
		{
			MessageBox("First insertion stopped due to max att!");
			Me.KillTimer(TIMER_ATT);
			btnStop.HideWindow();
			btnBack.ShowWindow();
			return;
		}
		else if (curAttValue == 150 && curCrystalCount == 0)
		{
			MessageBox("First insertion stopped due to max att with stones!");
			Me.KillTimer(TIMER_ATT);
			btnStop.HideWindow();
			btnBack.ShowWindow();
			return;
		}
		else if (curAttValue < 150 && curStoneCount == 0 && !isMainCrystalChosen)
		{
			MessageBox("First insertion stopped due to lack of stones!");
			Me.KillTimer(TIMER_ATT);
			btnStop.HideWindow();
			btnBack.ShowWindow();
			return;
		}
		else if (curAttValue < 150 && curStoneCount == 0 && curCrystalCount == 0)
		{
			MessageBox("First insertion stopped due to lack of stones and crystals!");
			Me.KillTimer(TIMER_ATT);
			btnStop.HideWindow();
			btnBack.ShowWindow();
			return;
		}
		else if (curAttValue >= 150 && curAttValue < 300 && curCrystalCount == 0)
		{
			MessageBox("First insertion stopped due to lack of crystals!");
			Me.KillTimer(TIMER_ATT);
			btnStop.HideWindow();
			btnBack.ShowWindow();
			return;
		}
	
		if (curAttValue < 150 && isMainCrystalChosen && isMainStoneChosen && curCrystalCount > 0 && curStoneCount == 0)
		{
			RequestUseItem(crystalInfo.ID);
			//sysDebug("ENCHANTING WITH CRYSTALS WHEN STONE COUNT 0");
		}
		else if (curAttValue < 150 && isMainStoneChosen && curStoneCount > 0)
		{
			RequestUseItem(stoneInfo.ID);
			//sysDebug("ENCHANTING WITH STONES");
		}
		else if (curAttValue < 150 && !isMainStoneChosen && curCrystalCount > 0)
		{
			RequestUseItem(crystalInfo.ID);
			//sysDebug("ENCHANTING WITH CRYSTALS WHEN NO STONE CHOSEN");
		}
		else if (curAttValue >= 150 && isMainCrystalChosen && curCrystalCount > 0)
		{
			RequestUseItem(crystalInfo.ID);
			//sysDebug("ENCHANTING WITH CRYSTALS");
		}
		else
		{
			MessageBox("Finished!");//DELETE THIS
			Me.KillTimer(TIMER_ATT);
			btnStop.HideWindow();
			btnBack.ShowWindow();
		}
	}
	else
	{
		
		curAttValue = GetDynamicArmorAttValue();
		
		if (!flagFirstStop)
		{
			if (curAttValue == 120)
			{
				flagFirstStop = true;
				MessageBox("First insertion stopped due to max att!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue == 60 && curCrystalCount == 0)
			{
				flagFirstStop = true;
				MessageBox("First insertion stopped due to max att with stones!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue < 60 && curStoneCount == 0 && !isMainCrystalChosen)
			{
				flagFirstStop = true;
				MessageBox("First insertion stopped due to lack of stones!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue < 60 && curStoneCount == 0 && curCrystalCount == 0)
			{
				flagFirstStop = true;
				MessageBox("First insertion stopped due to lack of stones and crystals!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue >= 60 && curAttValue < 120 && curCrystalCount == 0)
			{
				flagFirstStop = true;
				MessageBox("First insertion stopped due to lack of crystals!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			
			if (curAttValue < 60 && isMainCrystalChosen && isMainStoneChosen && curCrystalCount > 0 && curStoneCount == 0)
			{
				RequestUseItem(crystalInfo.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS WHEN STONE COUNT 0");
			}
			else if (curAttValue < 60 && isMainStoneChosen && curStoneCount > 0)
			{
				RequestUseItem(stoneInfo.ID);
				//sysDebug("ENCHANTING WITH STONES");
			}
			else if (curAttValue < 60 && !isMainStoneChosen && curCrystalCount > 0)
			{
				RequestUseItem(crystalInfo.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS WHEN NO STONE CHOSEN");
			}
			else if (curAttValue >= 60 && isMainCrystalChosen && curCrystalCount > 0)
			{
				RequestUseItem(crystalInfo.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS");
			}
			else
			{
				MessageBox("Finished!");//DELETE THIS
				Me.KillTimer(TIMER_ATT);
			}	
		} 
		else if (!flagSecondStop)
		{
			if (curAttValue == 120)
			{
				flagSecondStop = true;
				MessageBox("Second insertion stopped due to max att!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue == 60 && curCrystalCount2 == 0)
			{
				flagSecondStop = true;
				MessageBox("Second insertion stopped due to max att with stones!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue < 60 && curStoneCount2 == 0 && !isMainCrystalChosen2)
			{
				flagSecondStop = true;
				MessageBox("Second insertion stopped due to lack of stones!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue < 60 && curStoneCount2 == 0 && curCrystalCount2 == 0)
			{
				flagSecondStop = true;
				MessageBox("Second insertion stopped due to lack of stones and crystals!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue >= 60 && curAttValue < 120 && curCrystalCount2 == 0)
			{
				flagSecondStop = true;
				MessageBox("Second insertion stopped due to lack of crystals!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			
			if (curAttValue < 60 && isMainCrystalChosen2 && isMainStoneChosen2 && curCrystalCount2 > 0 && curStoneCount2 == 0)
			{
				RequestUseItem(crystalInfo2.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS WHEN STONE COUNT 0");
			}
			else if (curAttValue < 60 && isMainStoneChosen2 && curStoneCount2 > 0)
			{
				RequestUseItem(stoneInfo2.ID);
				//sysDebug("ENCHANTING WITH STONES");
			}
			else if (curAttValue < 60 && !isMainStoneChosen2 && curCrystalCount2 > 0)
			{
				RequestUseItem(crystalInfo2.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS WHEN NO STONE CHOSEN");
			}
			else if (curAttValue >= 60 && isMainCrystalChosen2 && curCrystalCount2 > 0)
			{
				RequestUseItem(crystalInfo2.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS");
			}
			else
			{
				MessageBox("Finished!");//DELETE THIS
				Me.KillTimer(TIMER_ATT);
				btnStop.HideWindow();
				btnBack.ShowWindow();
			}
		}
		else if (!flagThirdStop)
		{
			if (curAttValue == 120)
			{
				flagThirdStop = true;
				MessageBox("Third insertion stopped due to max att!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue == 60 && curCrystalCount3 == 0)
			{
				flagThirdStop = true;
				MessageBox("Third insertion stopped due to max att with stones!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue < 60 && curStoneCount3 == 0 && !isMainCrystalChosen3)
			{
				flagThirdStop = true;
				MessageBox("Third insertion stopped due to lack of stones!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue < 60 && curStoneCount3 == 0 && curCrystalCount3 == 0)
			{
				flagThirdStop = true;
				MessageBox("Third insertion stopped due to lack of stones!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			else if (curAttValue >= 60 && curAttValue < 120 && curCrystalCount3 == 0)
			{
				flagThirdStop = true;
				MessageBox("Third insertion stopped due to lack of crystals!");
				Me.KillTimer(TIMER_ATT);
				Me.SetTimer(TIMER_ATT, 250);
				return;
			}
			
			if (curAttValue < 60 && isMainCrystalChosen3 && isMainStoneChosen3 && curCrystalCount3 > 0 && curStoneCount3 == 0)
			{
				RequestUseItem(crystalInfo3.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS WHEN STONE COUNT 0");
			}
			else if (curAttValue < 60 && isMainStoneChosen3 && curStoneCount3 > 0)
			{
				RequestUseItem(stoneInfo3.ID);
				//sysDebug("ENCHANTING WITH STONES");
			}
			else if (curAttValue < 60 && !isMainStoneChosen3 && curCrystalCount3 > 0)
			{
				RequestUseItem(crystalInfo3.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS WHEN NO STONE CHOSEN");
			}
			else if (curAttValue >= 60 && isMainCrystalChosen3 && curCrystalCount3 > 0)
			{
				RequestUseItem(crystalInfo3.ID);
				//sysDebug("ENCHANTING WITH CRYSTALS");
			}
			else
			{
				MessageBox("Finished!");//DELETE THIS
				Me.KillTimer(TIMER_ATT);
				btnStop.HideWindow();
				btnBack.ShowWindow();
			}
		}
		else
		{
			flagFirstStop = false;
			flagSecondStop = false;
			flagThirdStop = false;
			if (mainStoneType != "")
			{
				txtProgress1.SetText(string(GetAttValue(mainInfo, ReverseATT(mainStoneType))));
			} else
			{
				txtProgress1.SetText(string(GetAttValue(mainInfo, ReverseATT(mainCrystalType))));
			}
			if (mainStoneType2 != "")
			{
				txtProgress2.SetText(string(GetAttValue(mainInfo, ReverseATT(mainStoneType2))));
			} else
			{
				txtProgress2.SetText(string(GetAttValue(mainInfo, ReverseATT(mainCrystalType2))));
			}
			if (mainStoneType3 != "")
			{
				txtProgress3.SetText(string(GetAttValue(mainInfo, ReverseATT(mainStoneType3))));
			} else
			{
				txtProgress3.SetText(string(GetAttValue(mainInfo, ReverseATT(mainCrystalType3))));
			}
			MessageBox("Insertion complete!");
			Me.KillTimer(TIMER_ATT);
			btnStop.HideWindow();
			btnBack.ShowWindow();
		}
	}
}

function OnClickContinue()
{
	local ItemInfo check;
	
	if (!itemMainStone.GetItem(0, check) && !itemMainCrystal.GetItem(0, check) && isWeapon)
	{
		MessageBox("NO STONES OR CRYSTALS CHOSEN!");
		isMainStoneChosen = false;
		isMainCrystalChosen = false;
		return;
	}
	else if (!itemMainStone.GetItem(0, check) && !itemMainCrystal.GetItem(0, check) && !isWeapon)
	{
		isMainStoneChosen = false;
		isMainCrystalChosen = false;
		flagFirstStop = true;
	}
	else if (!itemMainStone.GetItem(0, check) && itemMainCrystal.GetItem(0, check))
	{
		isMainStoneChosen = false;
		isMainCrystalChosen = true;
	}
	else if (itemMainStone.GetItem(0, check) && !itemMainCrystal.GetItem(0, check))
	{
		isMainStoneChosen = true;
		isMainCrystalChosen = false;
	}
	
	if (mainStoneType != mainCrystalType && mainStoneType != "" && mainCrystalType != "")
	{
		MessageBox("STONE AND CRYSTAL DO NOT MATCH!");
		return;
	}
	
	if (mainStoneType2 != mainCrystalType2 && mainStoneType2 != "" && mainCrystalType2 != "")
	{
		MessageBox("STONE AND CRYSTAL DO NOT MATCH!");
		return;
	}
	
	if (mainStoneType3 != mainCrystalType3 && mainStoneType3 != "" && mainCrystalType3 != "")
	{
		MessageBox("STONE AND CRYSTAL DO NOT MATCH!");
		return;
	}
	
	itemFire.HideWindow();
	itemWater.HideWindow();
	itemEarth.HideWindow();
	itemWind.HideWindow();
	itemHoly.HideWindow();
	itemDark.HideWindow();
	
	itemFireCrystal.HideWindow();
	itemWaterCrystal.HideWindow();
	itemEarthCrystal.HideWindow();
	itemWindCrystal.HideWindow();
	itemHolyCrystal.HideWindow();
	itemDarkCrystal.HideWindow();
	
	bgFireStone.HideWindow();
	bgWaterStone.HideWindow();
	bgEarthStone.HideWindow();
	bgWindStone.HideWindow();
	bgDarkStone.HideWindow();
	bgHolyStone.HideWindow();
	
	bgFireCrystal.HideWindow();
	bgWaterCrystal.HideWindow();
	bgEarthCrystal.HideWindow();
	bgWindCrystal.HideWindow();
	bgDarkCrystal.HideWindow();
	bgHolyCrystal.HideWindow();
	
	txtFireCount.HideWindow();
	txtWaterCount.HideWindow();
	txtEarthCount.HideWindow();
	txtWindCount.HideWindow();
	txtDarkCount.HideWindow();
	txtHolyCount.HideWindow();
	
	txtFireCrystal.HideWindow();
	txtWaterCrystal.HideWindow();
	txtEarthCrystal.HideWindow();
	txtWindCrystal.HideWindow();
	txtDarkCrystal.HideWindow();
	txtHolyCrystal.HideWindow();
	
	txtDescFire.HideWindow();
	txtDescWater.HideWindow();
	txtDescEarth.HideWindow();
	txtDescWind.HideWindow();
	txtDescDark.HideWindow();
	txtDescHoly.HideWindow();
	
	if (!isWeapon)
	{
		//Set BOOLs for 2nd & 3rd attribute
		if (!itemMainStone2.GetItem(0, check) && !itemMainCrystal2.GetItem(0, check))
		{
			isMainStoneChosen2 = false;
			isMainCrystalChosen2 = false;
			flagSecondStop = true;
		} else if (itemMainStone2.GetItem(0, check) && !itemMainCrystal2.GetItem(0, check))
		{
			isMainStoneChosen2 = true;
			isMainCrystalChosen2 = false;
		} else if (!itemMainStone2.GetItem(0, check) && itemMainCrystal2.GetItem(0, check))
		{
			isMainStoneChosen2 = false;
			isMainCrystalChosen2 = true;
		}
		
		if (!itemMainStone3.GetItem(0, check) && !itemMainCrystal3.GetItem(0, check))
		{
			isMainStoneChosen3 = false;
			isMainCrystalChosen3 = false;
			flagThirdStop = true;
		} else if (itemMainStone3.GetItem(0, check) && !itemMainCrystal3.GetItem(0, check))
		{
			isMainStoneChosen3 = true;
			isMainCrystalChosen3 = false;
		} else if (!itemMainStone3.GetItem(0, check) && itemMainCrystal3.GetItem(0, check))
		{
			isMainStoneChosen3 = false;
			isMainCrystalChosen3 = true;
		}
		
		//Set bar textures with reversed ones
		if (mainStoneType != "")
		{
			SetBarTextures(attProgress1, ReverseATT(mainStoneType));
		}
		else
		{
			SetBarTextures(attProgress1, ReverseATT(mainCrystalType));
		}
		
		if (mainStoneType2 != "")
		{
			SetBarTextures(attProgress2, ReverseATT(mainStoneType2));
		}
		else
		{
			SetBarTextures(attProgress2, ReverseATT(mainCrystalType2));
		}
		if (mainStoneType3 != "")
		{
			SetBarTextures(attProgress3, ReverseATT(mainStoneType3));
		}
		else
		{
			SetBarTextures(attProgress3, ReverseATT(mainCrystalType3));
		}
		
		if (armorEnchantOption == 0)
		{
			attProgress1.SetValue( 60, RequestArmorFirstAttValue() );
			txtProgress1.SetText( string( RequestArmorFirstAttValue() ) );
			//sysDebug("60 set - First: " $ string(RequestArmorFirstAttValue()));
		}
		else if (armorEnchantOption != -1)
		{
			attProgress1.SetValue( 120, RequestArmorFirstAttValue() );
			txtProgress1.SetText( string( RequestArmorFirstAttValue() ) );
			//sysDebug("120 set - First: " $ string(RequestArmorFirstAttValue()));
		}
		
		if (armorEnchantOption2 == 0)
		{
			attProgress2.SetValue( 60, RequestArmorSecondAttValue() );
			txtProgress2.SetText( string( RequestArmorSecondAttValue() ) );
			//sysDebug("60 set - Second: " $ string(RequestArmorSecondAttValue()));
		}
		else if (armorEnchantOption2 != -1)
		{
			attProgress2.SetValue( 120, RequestArmorSecondAttValue() );
			txtProgress2.SetText( string( RequestArmorSecondAttValue() ) );
			//sysDebug("120 set - Second: " $ string(RequestArmorSecondAttValue()));
		}
		
		if (armorEnchantOption3 == 0)
		{
			attProgress3.SetValue( 60, RequestArmorThirdAttValue() );
			txtProgress3.SetText( string( RequestArmorThirdAttValue() ) );
			//sysDebug("60 set - Third: " $ string(RequestArmorThirdAttValue()));
		}
		else if (armorEnchantOption3 != -1)
		{
			attProgress3.SetValue( 120, RequestArmorThirdAttValue() );
			txtProgress3.SetText( string( RequestArmorThirdAttValue() ) );
			//sysDebug("120 set - Third: " $ string(RequestArmorThirdAttValue()));
		}
		
		if (!flagFirstStop)
		{
			curAttValue = AddCurAttValue(armorFirstAtt, 0);
			//sysDebug("curAtt1:" @ string(curAttValue));
		} 
		else if (!flagSecondStop)
		{
			curAttValue = AddCurAttValue(armorSecondAtt, 0);
			//sysDebug("curAtt2:" @ string(curAttValue));
		}
		else if (!flagThirdStop)
		{
			curAttValue = AddCurAttValue(armorThirdAtt, 0);
			//sysDebug("curAtt3:" @ string(curAttValue));
		}
		else
		{
			MessageBox("ERROR: NO ATT TO INSERT!");
		}
		
		itemMainStone.SetAnchor("AutoAtt.itemMainArmor", "BottomCenter", "BottomCenter", -72, 44);
		itemMainCrystal.SetAnchor("AutoAtt.itemMainStone", "TopLeft", "TopLeft", 144, 0);
		
		attProgress1.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 114);
		attProgress2.SetAnchor("AutoAtt.attProgress1", "TopCenter", "TopCenter", 0, 40);
		attProgress3.SetAnchor("AutoAtt.attProgress2", "TopCenter", "TopCenter", 0, 40);
		if (isMainCrystalChosen || isMainStoneChosen)
		{
			attProgress1.ShowWindow();
			txtProgress1.ShowWindow();
		}
		if (isMainCrystalChosen2 || isMainStoneChosen2)
		{
			attProgress2.ShowWindow();
			txtProgress2.ShowWindow();	
		}
		if (isMainCrystalChosen3 || isMainStoneChosen3)
		{
			attProgress3.ShowWindow();
			txtProgress3.ShowWindow();
		}
		
		btnContinue.HideWindow();
		txtGuide.SetText("");
		btnInsert.ShowWindow();
		
		itemMainArmor.DisableWindow();
		itemMainStone.DisableWindow();
		itemMainStone2.DisableWindow();
		itemMainStone3.DisableWindow();
		itemMainCrystal.DisableWindow();
		itemMainCrystal2.DisableWindow();
		itemMainCrystal3.DisableWindow();
		
	}
	else
	{
		if (mainStoneType != "")
		{
			SetBarTextures(attProgress1, mainStoneType);
		}
		else
		{
			SetBarTextures(attProgress1, mainCrystalType);
		}
		
		if (weapEnchantOption == 0)
		{
			attProgress1.SetValue( 150, curAttValue );
			//sysDebug("150 set");
		}
		else
		{
			attProgress1.SetValue( 300, curAttValue );
			//sysDebug("300 set");
		}
		
		itemMainWeapon.SetAnchor("AutoAtt", "TopCenter", "TopCenter", -36, 72);
		
		attProgress1.SetAnchor("AutoAtt.itemMainStone", "BottomCenter", "BottomCenter", 0, 20);
		attProgress1.ShowWindow();
		
		txtProgress1.SetText(string(curAttValue));
		txtProgress1.ShowWindow();
		
		btnContinue.HideWindow();
		txtGuide.SetText("");
		btnInsert.ShowWindow();
		
		itemMainWeapon.DisableWindow();
		itemMainStone.DisableWindow();
		itemMainCrystal.DisableWindow();
		
		//sysDebug("BOOLS: STONE - " @ isMainStoneChosen @ "CRYSTAL - " @ isMainCrystalChosen);
	}
}

function OnRClickItem( String strID, int index )
{
	switch (strID)
	{
		case "itemMainWeapon":
			ClearItem();
		break;
		case "itemMainArmor":
			ClearItem();
		break;
	}
}

function int NeedAttForWeapon(ItemInfo weapInf)
{
	local int value;
	value = weapInf.AttackAttributeValue;
	
	if (value == 0)
	{
		curAttValue = 0;
		return 0;
	}
	else if (value < 150)
	{
		//sysDebug("NEED STONES");
		curAttValue = value;
		return 1;
	}
	else if (value >= 150 && value < 300)
	{
		//sysDebug("NEED CRYSTALS");
		curAttValue = value;
		return 2;
	}
	else
	{
		//sysDebug("WRONG ATT VALUE!");
		return -1;
	}
}

function int CalcStonesAtt(int sID, string type)
{
	local int index;
	local ItemID itemID;
	local ItemInfo Info;
	
	itemID.ClassID = sID;
	itemID.ServerID = 0;
	
	index = ihandle.FindItem(itemID);
	
	if (index == -1)
	{
		return 0;
	}
	
	if (ihandle.GetItem(index, Info))
	{
		switch (type)
		{
			case "fire":
				fireStoneInfo = Info;
			break;
			case "water":
				waterStoneInfo = Info;
			break;
			case "earth":
				earthStoneInfo = Info;
			break;
			case "wind":
				windStoneInfo = Info;
			break;
			case "holy":
				holyStoneInfo = Info;
			break;
			case "dark":
				darkStoneInfo = Info;
			break;
		}
		return Int64ToInt(Info.ItemNum);
	}
	else
	{
		return 0;
	}
}

function int CalcCrystalsAtt(int sID, string type)
{
	local int index;
	local ItemID itemID;
	local ItemInfo Info;
	
	itemID.ClassID = sID;
	itemID.ServerID = 0;
	
	index = ihandle.FindItem(itemID);
	
	if (index == -1)
	{
		return 0;
	}
	
	if (ihandle.GetItem(index, Info))
	{
		switch (type)
		{
			case "fire":
				fireCrystalInfo = Info;
			break;
			case "water":
				waterCrystalInfo = Info;
			break;
			case "earth":
				earthCrystalInfo = Info;
			break;
			case "wind":
				windCrystalInfo = Info;
			break;
			case "holy":
				holyCrystalInfo = Info;
			break;
			case "dark":
				darkCrystalInfo = Info;
			break;
		}
		return Int64ToInt(Info.ItemNum);
	}
	else
	{
		return 0;
	}
}

function ChooseAtt()
{
	local int fireCount;
	local int waterCount;
	local int earthCount;
	local int windCount;
	local int holyCount;
	local int darkCount;
	
	local int fireCrystalCount;
	local int waterCrystalCount;
	local int earthCrystalCount;
	local int windCrystalCount;
	local int holyCrystalCount;
	local int darkCrystalCount;
	
	fireCount = CalcStonesAtt(9546, "fire");
	fireCrystalCount = CalcCrystalsAtt(9552, "fire");
	itemFire.Clear();
	itemFire.AddItem(fireStoneInfo);
	itemFireCrystal.Clear();
	itemFireCrystal.AddItem(fireCrystalInfo);
	txtFireCount.SetText(string(fireCount));
	txtFireCrystal.SetText(string(fireCrystalCount));
	if (fireCount == 0) itemFire.DisableWindow();
	else itemFire.EnableWindow();
	if (fireCrystalCount == 0) itemFireCrystal.DisableWindow();
	else itemFireCrystal.EnableWindow();
		
	waterCount = CalcStonesAtt(9547, "water");
	waterCrystalCount = CalcCrystalsAtt(9553, "water");
	itemWater.Clear();
	itemWater.AddItem(waterStoneInfo);
	itemWaterCrystal.Clear();
	itemWaterCrystal.AddItem(waterCrystalInfo);
	txtWaterCount.SetText(string(waterCount));
	txtWaterCrystal.SetText(string(waterCrystalCount));
	if (waterCount == 0) itemWater.DisableWindow();
	else itemWater.EnableWindow();
	if (waterCrystalCount == 0) itemWaterCrystal.DisableWindow();
	else itemWaterCrystal.EnableWindow();
	
	earthCount = CalcStonesAtt(9548, "earth");
	earthCrystalCount = CalcCrystalsAtt(9554, "earth");
	itemEarth.Clear();
	itemEarth.AddItem(earthStoneInfo);
	itemEarthCrystal.Clear();
	itemEarthCrystal.AddItem(earthCrystalInfo);
	txtEarthCount.SetText(string(earthCount));
	txtEarthCrystal.SetText(string(earthCrystalCount));
	if (earthCount == 0) itemEarth.DisableWindow();
	else itemEarth.EnableWindow();
	if (earthCrystalCount == 0) itemEarthCrystal.DisableWindow();
	else itemEarthCrystal.EnableWindow();
	
	windCount = CalcStonesAtt(9549, "wind");
	windCrystalCount = CalcCrystalsAtt(9555, "wind");
	itemWind.Clear();
	itemWind.AddItem(windStoneInfo);
	itemWindCrystal.Clear();
	itemWindCrystal.AddItem(windCrystalInfo);
	txtWindCount.SetText(string(windCount));
	txtWindCrystal.SetText(string(windCrystalCount));
	if (windCount == 0) itemWind.DisableWindow();
	else itemWind.EnableWindow();
	if (windCrystalCount == 0) itemWindCrystal.DisableWindow();
	else itemWindCrystal.EnableWindow();
	
	darkCount = CalcStonesAtt(9550, "dark");
	darkCrystalCount = CalcCrystalsAtt(9556, "dark");
	itemDark.Clear();
	itemDark.AddItem(darkStoneInfo);
	itemDarkCrystal.Clear();
	itemDarkCrystal.AddItem(darkCrystalInfo);
	txtDarkCount.SetText(string(darkCount));
	txtDarkCrystal.SetText(string(darkCrystalCount));
	if (darkCount == 0) itemDark.DisableWindow();
	else itemDark.EnableWindow();
	if (darkCrystalCount == 0) itemDarkCrystal.DisableWindow();
	else itemDarkCrystal.EnableWindow();
	
	holyCount = CalcStonesAtt(9551, "holy");
	holyCrystalCount = CalcCrystalsAtt(9557, "holy");
	itemHoly.Clear();
	itemHoly.AddItem(holyStoneInfo);
	itemHolyCrystal.Clear();
	itemHolyCrystal.AddItem(holyCrystalInfo);
	txtHolyCount.SetText(string(holyCount));
	txtHolyCrystal.SetText(string(holyCrystalCount));
	if (holyCount == 0) itemHoly.DisableWindow();
	else itemHoly.EnableWindow();
	if (holyCrystalCount == 0) itemHolyCrystal.DisableWindow();
	else itemHolyCrystal.EnableWindow();
	
	
	//itemMainWeapon.SetAnchor("AutoAtt", "TopCenter", "TopCenter", -36, 58);
	
	itemFire.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -92, 40);
	itemEarth.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -56, 40);
	itemDark.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 40);

	itemWater.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 40);
	itemWind.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 56, 40);
	itemHoly.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 92, 40);
	
	txtFireCount.ShowWindow();
	txtWaterCount.ShowWindow();
	txtEarthCount.ShowWindow();
	txtWindCount.ShowWindow();
	txtDarkCount.ShowWindow();
	txtHolyCount.ShowWindow();
	
	txtFireCrystal.ShowWindow();
	txtWaterCrystal.ShowWindow();
	txtEarthCrystal.ShowWindow();
	txtWindCrystal.ShowWindow();
	txtDarkCrystal.ShowWindow();
	txtHolyCrystal.ShowWindow();
	
	bgFireStone.ShowWindow();
	bgWaterStone.ShowWindow();
	bgEarthStone.ShowWindow();
	bgWindStone.ShowWindow();
	bgDarkStone.ShowWindow();
	bgHolyStone.ShowWindow();
	
	bgFireCrystal.ShowWindow();
	bgWaterCrystal.ShowWindow();
	bgEarthCrystal.ShowWindow();
	bgWindCrystal.ShowWindow();
	bgDarkCrystal.ShowWindow();
	bgHolyCrystal.ShowWindow();
	
	itemFire.ShowWindow();
	itemWater.ShowWindow();
	itemEarth.ShowWindow();
	itemWind.ShowWindow();
	itemHoly.ShowWindow();
	itemDark.ShowWindow();
	
	itemFireCrystal.ShowWindow();
	itemWaterCrystal.ShowWindow();
	itemEarthCrystal.ShowWindow();
	itemWindCrystal.ShowWindow();
	itemHolyCrystal.ShowWindow();
	itemDarkCrystal.ShowWindow();
}

function ShowNeededAttForWeapon()
{
	local string type;
	local int value;
	
	local int fireCount;
	local int waterCount;
	local int earthCount;
	local int windCount;
	local int holyCount;
	local int darkCount;
	
	local int fireCrystalCount;
	local int waterCrystalCount;
	local int earthCrystalCount;
	local int windCrystalCount;
	local int holyCrystalCount;
	local int darkCrystalCount;
	
	type = GetWeaponAttTypeByID(mainInfo.AttackAttributeType);
	value = mainInfo.AttackAttributeValue;
	txtProgress1.SetText(string(value));
	
	//sysDebug("type" @ type);
	
	switch (type)
	{
		case "fire":
			fireCount = CalcStonesAtt(9546, "fire");
			fireCrystalCount = CalcCrystalsAtt(9552, "fire");
			itemFire.Clear();
			itemFire.AddItem(fireStoneInfo);
			itemFireCrystal.Clear();
			itemFireCrystal.AddItem(fireCrystalInfo);
			txtFireCount.SetText(string(fireCount));
			txtFireCrystal.SetText(string(fireCrystalCount));
			if (fireCount == 0) itemFire.DisableWindow();
			else itemFire.EnableWindow();
			if (fireCrystalCount == 0) itemFireCrystal.DisableWindow();
			else itemFireCrystal.EnableWindow();
			itemFire.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 40);
			itemFireCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 40);
			if (value < 150)
			{
				itemFire.ShowWindow();
				txtFireCount.ShowWindow();
				bgFireStone.ShowWindow();
			}
			else
			{
				itemFireCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 0, 40);
			}
			itemFireCrystal.ShowWindow();
			txtFireCrystal.ShowWindow();
			bgFireCrystal.ShowWindow();
		break;
		case "water":
			waterCount = CalcStonesAtt(9547, "water");
			waterCrystalCount = CalcCrystalsAtt(9553, "water");
			itemWater.Clear();
			itemWater.AddItem(waterStoneInfo);
			itemWaterCrystal.Clear();
			itemWaterCrystal.AddItem(waterCrystalInfo);
			txtWaterCount.SetText(string(waterCount));
			txtWaterCrystal.SetText(string(waterCrystalCount));
			if (waterCount == 0) itemWater.DisableWindow();
			else itemWater.EnableWindow();
			if (waterCrystalCount == 0) itemWaterCrystal.DisableWindow();
			else itemWaterCrystal.EnableWindow();
			itemWater.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 40);
			itemWaterCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 40);
			if (value < 150)
			{
				itemWater.ShowWindow();
				txtWaterCount.ShowWindow();
				bgWaterStone.ShowWindow();
			}
			else
			{
				itemWaterCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 0, 40);
			}
			itemWaterCrystal.ShowWindow();
			txtWaterCrystal.ShowWindow();
			bgWaterCrystal.ShowWindow();
		break;
		case "earth":
			earthCount = CalcStonesAtt(9548, "earth");
			earthCrystalCount = CalcCrystalsAtt(9554, "earth");
			itemEarth.Clear();
			itemEarth.AddItem(earthStoneInfo);
			itemEarthCrystal.Clear();
			itemEarthCrystal.AddItem(earthCrystalInfo);
			txtEarthCount.SetText(string(earthCount));
			txtEarthCrystal.SetText(string(earthCrystalCount));
			if (earthCount == 0) itemEarth.DisableWindow();
			else itemEarth.EnableWindow();
			if (earthCrystalCount == 0) itemEarthCrystal.DisableWindow();
			else itemEarthCrystal.EnableWindow();
			itemEarth.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 40);
			itemEarthCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 40);
			if (value < 150)
			{
				itemEarth.ShowWindow();
				txtEarthCount.ShowWindow();
				bgEarthStone.ShowWindow();
			}
			else
			{
				itemEarthCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 0, 40);
			}
			itemEarthCrystal.ShowWindow();
			txtEarthCrystal.ShowWindow();
			bgEarthCrystal.ShowWindow();
		break;
		case "wind":
			windCount = CalcStonesAtt(9549, "wind");
			windCrystalCount = CalcCrystalsAtt(9555, "wind");
			itemWind.Clear();
			itemWind.AddItem(windStoneInfo);
			itemWindCrystal.Clear();
			itemWindCrystal.AddItem(windCrystalInfo);
			txtWindCount.SetText(string(windCount));
			txtWindCrystal.SetText(string(windCrystalCount));
			if (windCount == 0) itemWind.DisableWindow();
			else itemWind.EnableWindow();
			if (windCrystalCount == 0) itemWindCrystal.DisableWindow();
			else itemWindCrystal.EnableWindow();
			itemWind.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 40);
			itemWindCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 40);
			if (value < 150)
			{
				itemWind.ShowWindow();
				txtWindCount.ShowWindow();
				bgWindStone.ShowWindow();
			}
			else
			{
				itemWindCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 0, 40);
			}
			itemWindCrystal.ShowWindow();
			txtWindCrystal.ShowWindow();
			bgWindCrystal.ShowWindow();
		break;
		case "dark":
			darkCount = CalcStonesAtt(9550, "dark");
			darkCrystalCount = CalcCrystalsAtt(9556, "dark");
			itemDark.Clear();
			itemDark.AddItem(darkStoneInfo);
			itemDarkCrystal.Clear();
			itemDarkCrystal.AddItem(darkCrystalInfo);
			txtDarkCount.SetText(string(darkCount));
			txtDarkCrystal.SetText(string(darkCrystalCount));
			if (darkCount == 0) itemDark.DisableWindow();
			else itemDark.EnableWindow();
			if (darkCrystalCount == 0) itemDarkCrystal.DisableWindow();
			else itemDarkCrystal.EnableWindow();
			itemDark.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 40);
			itemDarkCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 40);
			if (value < 150)
			{
				itemDark.ShowWindow();
				txtDarkCount.ShowWindow();
				bgDarkStone.ShowWindow();
			}
			else
			{
				itemDarkCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 0, 40);
			}
			itemDarkCrystal.ShowWindow();
			txtDarkCrystal.ShowWindow();
			bgDarkCrystal.ShowWindow();
		break;
		case "holy":
			holyCount = CalcStonesAtt(9551, "holy");
			holyCrystalCount = CalcCrystalsAtt(9557, "holy");
			itemHoly.Clear();
			itemHoly.AddItem(holyStoneInfo);
			itemHolyCrystal.Clear();
			itemHolyCrystal.AddItem(holyCrystalInfo);
			txtHolyCount.SetText(string(holyCount));
			txtHolyCrystal.SetText(string(holyCrystalCount));
			if (holyCount == 0) itemHoly.DisableWindow();
			else itemHoly.EnableWindow();
			if (holyCrystalCount == 0) itemHolyCrystal.DisableWindow();
			else itemHolyCrystal.EnableWindow();
			itemHoly.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", -20, 40);
			itemHolyCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 20, 40);
			if (value < 150)
			{
				itemHoly.ShowWindow();
				txtHolyCount.ShowWindow();
				bgHolyStone.ShowWindow();
			}
			else
			{
				itemHolyCrystal.SetAnchor("AutoAtt.itemMainStone", "BottomLeft", "BottomLeft", 0, 40);
			}
			itemHolyCrystal.ShowWindow();
			txtHolyCrystal.ShowWindow();
			bgHolyCrystal.ShowWindow();
		break;
	}

}

function AddAttSelectionForWeapon(ItemInfo infItem)
{
	mainInfo = infItem;
	if((mainInfo.CrystalType > 4) && (mainInfo.ShieldDefense == 0) && (mainInfo.SlotBitType != 268435456) && (mainInfo.ArmorType != 4) && (mainInfo.SlotBitType != 1))
	{
		txtGuide.SetText("Place stones/crystals in appropriate slots.");
		if (needAttForWeapon(mainInfo) == 0)
		{
			//sysDebug("FUNCTION TO CHOOSE ATT");
			//ChooseAtt();
			itemMainWeapon.AddItem(mainInfo);
			mainID = mainInfo.ID;
		}
		else if (needAttForWeapon(mainInfo) > 0)
		{
			//sysDebug("FUNCTION TO CONTINUE WITH");
			//ShowNeededAttForWeapon();
			itemMainWeapon.AddItem(mainInfo);
			mainID = mainInfo.ID;
		}
		itemMainStone.EnableWindow();
		itemMainCrystal.EnableWindow();
	}
	else
	{
		MessageBox("Wrong item!");
	}
}

function AddAttSelectionForArmor(ItemInfo infItem)
{
	local int fireAtt;
	local int waterAtt;
	local int earthAtt;
	local int windAtt;
	local int darkAtt;
	local int holyAtt;
	local int attCountInArmor;
	//local int idx;
	
	mainInfo = infItem;
	if((mainInfo.CrystalType > 4) && (mainInfo.ShieldDefense == 0) && (mainInfo.SlotBitType != 268435456) && (mainInfo.ArmorType != 4) && (mainInfo.SlotBitType != 1))
	{
		fireAtt = GetAttValue(mainInfo, "fire");
		waterAtt = GetAttValue(mainInfo, "water");
		earthAtt = GetAttValue(mainInfo, "earth");
		windAtt = GetAttValue(mainInfo, "wind");
		darkAtt = GetAttValue(mainInfo, "dark");
		holyAtt = GetAttValue(mainInfo, "holy");
		
		if ( (fireAtt == 120 || waterAtt == 120) && (earthAtt == 120 || windAtt == 120) && (darkAtt == 120 || holyAtt == 120) )
		{
			MessageBox("Item is full attributed");
			return;
		}
		
		if (fireAtt == 60 || waterAtt == 60)
		{
			itemMainStone.DisableWindow();
			bgMainStone.DisableWindow();
			//sysDebug("First att at maximum 60");
		} else if (fireAtt == 120 || waterAtt == 120)
		{
			itemMainStone.DisableWindow();
			itemMainCrystal.DisableWindow();
			bgMainStone.DisableWindow();
			bgMainCrystal.DisableWindow();
			//sysDebug("First att at maximum 120");
		}
		
		if (earthAtt == 60 || windAtt == 60)
		{
			itemMainStone2.DisableWindow();
			bgMainStone2.DisableWindow();
			//sysDebug("Second att at maximum 60");
		} else if (earthAtt == 120 || windAtt == 120)
		{
			itemMainStone2.DisableWindow();
			itemMainCrystal2.DisableWindow();
			bgMainStone2.DisableWindow();
			bgMainCrystal2.DisableWindow();
			//sysDebug("Second att at maximum 120");
		}
		
		if (darkAtt == 60 || holyAtt == 60)
		{
			itemMainStone3.DisableWindow();
			bgMainStone3.DisableWindow();
			//sysDebug("Third att at maximum 60");
		} else if (darkAtt == 120 || holyAtt == 120)
		{
			itemMainStone3.DisableWindow();
			itemMainCrystal3.DisableWindow();
			bgMainStone3.DisableWindow();
			bgMainCrystal3.DisableWindow();
			//sysDebug("Third att at maximum 120");
		}
		
		if (fireAtt != 0)
		{
			attCountInArmor++;
			AttType[0] = fireAtt;
			armorFirstAtt = "fire";
			txtDescFire.ShowWindow();
			txtDescFire.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 114);
			txtDescWater.HideWindow();
		} 
		if (waterAtt != 0)
		{
			attCountInArmor++;
			AttType[1] = waterAtt;
			armorFirstAtt = "water";
			txtDescFire.HideWindow();
			txtDescWater.ShowWindow();
			txtDescWater.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 114);
		} 
		if (earthAtt != 0)
		{
			attCountInArmor++;
			AttType[2] = earthAtt;
			if (armorFirstAtt != "")
			{
				armorSecondAtt = "earth";
			}
			else
			{
				armorFirstAtt = "earth";
			}
			txtDescEarth.ShowWindow();
			txtDescEarth.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 154);
			txtDescWind.HideWindow();
		}
		if (windAtt != 0)
		{
			attCountInArmor++;
			AttType[3] = windAtt;
			if (armorFirstAtt != "")
			{
				armorSecondAtt = "wind";
			}
			else
			{
				armorFirstAtt = "wind";
			}
			txtDescWind.ShowWindow();
			txtDescWind.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 154);
			txtDescEarth.HideWindow();
		}
		if (darkAtt != 0)
		{
			attCountInArmor++;
			AttType[4] = darkAtt;
			if (armorFirstAtt != "")
			{
				if (armorSecondAtt != "")
				{
					armorThirdAtt = "dark";
				}
				else
				{
					armorSecondAtt = "dark";
				}
			}
			else
			{
				armorFirstAtt = "dark";
			}
			txtDescDark.ShowWindow();
			txtDescDark.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 194);
			txtDescHoly.HideWindow();
		}
		if (holyAtt != 0)
		{
			attCountInArmor++;
			AttType[5] = holyAtt;
			if (armorFirstAtt != "")
			{
				if (armorSecondAtt != "")
				{
					armorThirdAtt = "holy";
				}
				else
				{
					armorSecondAtt = "holy";
				}
			}
			else
			{
				armorFirstAtt = "holy";
			}
			txtDescHoly.ShowWindow();
			txtDescHoly.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 194);
			txtDescDark.HideWindow();
		}
		
		//if (attCountInArmor >= 0 && attCountInArmor <= 3) //DEL AFTER
		//{
			//sysDebug("Armor count is fine - " $ string(attCountInArmor));
			//sysDebug("ATT type order: " $ armorFirstAtt @ armorSecondAtt @ armorThirdAtt);
		//}
		
		itemMainArmor.Clear();
		itemMainArmor.AddItem(mainInfo);
		mainID = mainInfo.ID;
		txtGuide.SetText("Place stones/crystals in appropriate slots.");
		itemMainStone.EnableWindow();
		itemMainStone2.EnableWindow();
		itemMainStone3.EnableWindow();
		
		itemMainCrystal.EnableWindow();
		itemMainCrystal2.EnableWindow();
		itemMainCrystal3.EnableWindow();
	}
	else
	{
		MessageBox("Wrong item!");
	}
}

function OnDropItem( String a_WindowID, ItemInfo a_ItemInfo, int X, int Y)
{
	local int idx;
	//sysDebug(a_WindowID @ a_ItemInfo.Name);
	switch(a_WindowID)
	{
		case "itemMainWeapon":
			if (a_ItemInfo.ItemType == 0 && itemMainWeapon.IsEnableWindow()) //Weapon
			{
				ResetVariables("Weapon");
				if (a_ItemInfo.AttackAttributeValue == 300)
				{
					MessageBox("Attribute at its maximum!");
					return;
				}
				//bgMainItem.HideWindow();
				AddAttSelectionForWeapon(a_ItemInfo);
				isWeapon = true;
				//sysDebug("WEAPON CHOSEN!");
			}
			else
			{
				//sysDebug("ITEM INCORRECT!");
				isWeapon = false;
			}
		break;
		case "itemMainArmor":
			if (a_ItemInfo.ItemType == 1 && itemMainArmor.IsEnableWindow()) //Armor
			{
				
				ResetVariables("Armor");
				for (idx = 0; idx < 6; idx++)
				{
					AttType[idx] = 0;
				}
				
				
				AddAttSelectionForArmor(a_ItemInfo);
			}
		break;
		case "itemMainStone":
			if (!itemMainStone.IsEnableWindow())
			{
				return;
			}
			
			if (isStone(a_ItemInfo) && !isCrystal(a_ItemInfo))
			{
				
				if (InStr( Caps(a_ItemInfo.Name), Caps("Fire") ) > -1 )
				{
					mainStoneType = "fire";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Water") ) > -1 )
				{
					mainStoneType = "water";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Wind") ) > -1 )
				{
					mainStoneType = "wind";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Earth") ) > -1 )
				{
					mainStoneType = "earth";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Holy") ) > -1 )
				{
					mainStoneType = "holy";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Dark") ) > -1 )
				{
					mainStoneType = "dark";
				}
				
				if (!isWeapon)
				{
					if (RequestArmorFirstAttValue() >= 60)
					{
						MessageBox("First att is at maximum");
						mainStoneType = "";
						return;
					}
					
					if (mainStoneType == "fire" && ( armorFirstAtt == "fire"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainStoneType = "";
						return;
					}
					else if (mainStoneType == "water" && ( armorFirstAtt == "water"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainStoneType = "";
						return;
					}
					
					if (mainStoneType == "fire")
					{
						txtDescFire.HideWindow();
						txtDescWater.ShowWindow();
						txtDescWater.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 114);
					}
					else if (mainStoneType == "water")
					{
						txtDescWater.HideWindow();
						txtDescFire.ShowWindow();
						txtDescFire.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 114);
					}
					else
					{
						MessageBox("Not fire/water");
						mainStoneType = "";
						return;
					}
				}
				else
				{
					if ( GetWeaponAttTypeByID(mainInfo.AttackAttributeType) != mainStoneType && GetWeaponAttTypeByID(mainInfo.AttackAttributeType) != "" )
					{
						MessageBox("Wrong attribute is dropped!");
						mainStoneType = "";
						return;
					}
					
					if (mainInfo.AttackAttributeValue >= 150)
					{
						MessageBox("Maximum with stones!");
						mainStoneType = "";
						return;
					}
				}
				
				if (!isMainCrystalChosen)
				{
					txtGuide.SetText("Enchant with stones");
					weapEnchantOption = 0;
					armorEnchantOption = 0;
				}
				else
				{
					txtGuide.SetText("Enchant with stones + crystals");
					weapEnchantOption = 2;
					armorEnchantOption = 2;
				}
				
				bgMainStone.HideWindow();
				itemMainStone.Clear();
				itemMainStone.AddItem(a_ItemInfo);
				stoneInfo = a_ItemInfo;
				curStoneCount = CalcStonesAtt(a_ItemInfo.ID.ClassID, "");
				txtStoneCount.SetText( string( CalcStonesAtt(a_ItemInfo.ID.ClassID, "") ) );
				txtStoneCount.ShowWindow();
				isMainStoneChosen = true;
				
				btnContinue.ShowWindow();
				btnBack.HideWindow();
				
				//sysDebug("On drop stone");
			}
		break;
		case "itemMainCrystal":
			if (!itemMainCrystal.IsEnableWindow())
			{
				return;
			}
			if (!isStone(a_ItemInfo) && isCrystal(a_ItemInfo))
			{
				
				if (InStr( Caps(a_ItemInfo.Name), Caps("Fire") ) > -1 )
				{
					mainCrystalType = "fire";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Water") ) > -1 )
				{
					mainCrystalType = "water";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Wind") ) > -1 )
				{
					mainCrystalType = "wind";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Earth") ) > -1 )
				{
					mainCrystalType = "earth";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Holy") ) > -1 )
				{
					mainCrystalType = "holy";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Dark") ) > -1 )
				{
					mainCrystalType = "dark";
				}
				
				if (!isWeapon)
				{
					if (RequestArmorFirstAttValue() == 120)
					{
						//flagFirstStop = true;
						MessageBox("First att is at maximum");
						mainCrystalType = "";
						return;
					}
					
					if (mainCrystalType == "fire" && ( armorFirstAtt == "fire"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainCrystalType = "";
						return;
					}
					else if (mainCrystalType == "water" && ( armorFirstAtt == "water"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainCrystalType = "";
						return;
					}
					
					if (mainCrystalType == "fire")
					{
						txtDescFire.HideWindow();
						txtDescWater.ShowWindow();
						txtDescWater.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 114);
					}
					else if (mainCrystalType == "water")
					{
						txtDescWater.HideWindow();
						txtDescFire.ShowWindow();
						txtDescFire.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 114);
					}
					else
					{
						MessageBox("Not fire/water");
						mainCrystalType = "";
						return;
					}
				}
				else
				{
					if ( GetWeaponAttTypeByID(mainInfo.AttackAttributeType) != mainCrystalType && GetWeaponAttTypeByID(mainInfo.AttackAttributeType) != "" )
					{
						MessageBox("Wrong attribute is dropped!");
						mainCrystalType = "";
						return;
					}
				}
				
				if (!isMainStoneChosen)
				{
					txtGuide.SetText("Enchant with crystals");
					weapEnchantOption = 1;
					armorEnchantOption = 1;
				}
				else
				{
					txtGuide.SetText("Enchant with stones + crystals");
					weapEnchantOption = 2;
					armorEnchantOption = 2;
				}
				
				bgMainCrystal.HideWindow();
				itemMainCrystal.Clear();
				itemMainCrystal.AddItem(a_ItemInfo);
				crystalInfo = a_ItemInfo;
				curCrystalCount = CalcCrystalsAtt(a_ItemInfo.ID.ClassID, "");
				txtCrystalCount.SetText( string( CalcCrystalsAtt(a_ItemInfo.ID.ClassID, "") ) );
				txtCrystalCount.ShowWindow();
				isMainCrystalChosen = true;
				
				btnContinue.ShowWindow();
				btnBack.HideWindow();
				
				//sysDebug("On drop crystal");
			}
		break;
		case "itemMainStone2":
			if (!itemMainStone2.IsEnableWindow())
			{
				return;
			}
			if (isStone(a_ItemInfo) && !isCrystal(a_ItemInfo))
			{
				
				
				if (InStr( Caps(a_ItemInfo.Name), Caps("Fire") ) > -1 )
				{
					mainStoneType2 = "fire";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Water") ) > -1 )
				{
					mainStoneType2 = "water";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Wind") ) > -1 )
				{
					mainStoneType2 = "wind";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Earth") ) > -1 )
				{
					mainStoneType2 = "earth";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Holy") ) > -1 )
				{
					mainStoneType2 = "holy";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Dark") ) > -1 )
				{
					mainStoneType2 = "dark";
				}
				
				if (!isWeapon)
				{
					if (RequestArmorSecondAttValue() >= 60)
					{
						//flagSecondStop = true;
						MessageBox("Second att is at maximum");
						mainStoneType2 = "";
						return;
					}
					
					if (mainStoneType2 == "earth" && ( armorFirstAtt == "earth" || armorSecondAtt == "earth" ))
					{
						MessageBox("Opposite attribute is dropped!");
						mainStoneType2 = "";
						return;
					}
					else if (mainStoneType2 == "wind" && ( armorFirstAtt == "wind" || armorSecondAtt == "wind" ))
					{
						MessageBox("Opposite attribute is dropped!");
						mainStoneType2 = "";
						return;
					}
					
					if (mainStoneType2 == "earth")
					{
						txtDescEarth.HideWindow();
						txtDescWind.ShowWindow();
						txtDescWind.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 154);
					}
					else if (mainStoneType2 == "wind")
					{
						txtDescWind.HideWindow();
						txtDescEarth.ShowWindow();
						txtDescEarth.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 154);
					}
					else
					{
						MessageBox("Not earth/wind");
						mainStoneType2 = "";
						return;
					}
				}
				
				if (!isMainCrystalChosen2)
				{
					txtGuide.SetText("Enchant with stones");
					armorEnchantOption2 = 0;
				}
				else
				{
					txtGuide.SetText("Enchant with stones + crystals");
					armorEnchantOption2 = 2;
				}
				
				bgMainStone2.HideWindow();
				itemMainStone2.Clear();
				itemMainStone2.AddItem(a_ItemInfo);
				stoneInfo2 = a_ItemInfo;
				curStoneCount2 = CalcStonesAtt(a_ItemInfo.ID.ClassID, "");
				txtStoneCount2.SetText( string( CalcStonesAtt(a_ItemInfo.ID.ClassID, "") ) );
				txtStoneCount2.ShowWindow();
				isMainStoneChosen2 = true;
				
				btnContinue.ShowWindow();
				btnBack.HideWindow();
				
				//sysDebug("On drop stone 2");
			}
		break;
		case "itemMainCrystal2":
			if (!itemMainCrystal2.IsEnableWindow())
			{
				return;
			}
			if (!isStone(a_ItemInfo) && isCrystal(a_ItemInfo))
			{
				
				if (InStr( Caps(a_ItemInfo.Name), Caps("Fire") ) > -1 )
				{
					mainCrystalType2 = "fire";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Water") ) > -1 )
				{
					mainCrystalType2 = "water";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Wind") ) > -1 )
				{
					mainCrystalType2 = "wind";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Earth") ) > -1 )
				{
					mainCrystalType2 = "earth";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Holy") ) > -1 )
				{
					mainCrystalType2 = "holy";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Dark") ) > -1 )
				{
					mainCrystalType2 = "dark";
				}
				
				if (!isWeapon)
				{
					if (RequestArmorSecondAttValue() == 120)
					{
						//flagSecondStop = true;
						MessageBox("Second att is at maximum");
						mainCrystalType2 = "";
						return;
					}
					
					if (mainCrystalType2 == "earth" && ( armorFirstAtt == "earth" || armorSecondAtt == "earth" ))
					{
						MessageBox("Opposite attribute is dropped!");
						mainCrystalType2 = "";
						return;
					}
					else if (mainCrystalType2 == "wind" && ( armorFirstAtt == "wind" || armorSecondAtt == "wind" ))
					{
						MessageBox("Opposite attribute is dropped!");
						mainCrystalType2 = "";
						return;
					}
					
					if (mainCrystalType2 == "earth")
					{
						txtDescEarth.HideWindow();
						txtDescWind.ShowWindow();
						txtDescWind.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 154);
					}
					else if (mainCrystalType2 == "wind")
					{
						txtDescWind.HideWindow();
						txtDescEarth.ShowWindow();
						txtDescEarth.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 154);
					}
					else
					{
						MessageBox("Not earth/wind");
						mainCrystalType2 = "";
						return;
					}
				}
				
				if (!isMainStoneChosen2)
				{
					txtGuide.SetText("Enchant with crystals");
					armorEnchantOption2 = 1;
				}
				else
				{
					txtGuide.SetText("Enchant with stones + crystals");
					armorEnchantOption2 = 2;
				}
				
				bgMainCrystal2.HideWindow();
				itemMainCrystal2.Clear();
				itemMainCrystal2.AddItem(a_ItemInfo);
				crystalInfo2 = a_ItemInfo;
				curCrystalCount2 = CalcCrystalsAtt(a_ItemInfo.ID.ClassID, "");
				txtCrystalCount2.SetText( string( CalcCrystalsAtt(a_ItemInfo.ID.ClassID, "") ) );
				txtCrystalCount2.ShowWindow();
				isMainCrystalChosen2 = true;
				
				btnContinue.ShowWindow();
				btnBack.HideWindow();
				
				//sysDebug("On drop crystal 2");
			}
		break;
		case "itemMainStone3":
			if (!itemMainStone3.IsEnableWindow())
			{
				return;
			}
			if (isStone(a_ItemInfo) && !isCrystal(a_ItemInfo))
			{
				
				
				if (InStr( Caps(a_ItemInfo.Name), Caps("Fire") ) > -1 )
				{
					mainStoneType3 = "fire";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Water") ) > -1 )
				{
					mainStoneType3 = "water";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Wind") ) > -1 )
				{
					mainStoneType3 = "wind";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Earth") ) > -1 )
				{
					mainStoneType3 = "earth";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Holy") ) > -1 )
				{
					mainStoneType3 = "holy";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Dark") ) > -1 )
				{
					mainStoneType3 = "dark";
				}
				
				if (!isWeapon)
				{
					if (RequestArmorThirdAttValue() >= 60)
					{
						//flagThirdStop = true;
						MessageBox("Third att is at maximum");
						mainStoneType3 = "";
						return;
					}
					
					if (mainStoneType3 == "dark" && ( armorFirstAtt == "dark" || armorSecondAtt == "dark" || armorThirdAtt == "dark"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainStoneType3 = "";
						return;
					}
					else if (mainStoneType3 == "holy" && ( armorFirstAtt == "holy" || armorSecondAtt == "holy" || armorThirdAtt == "holy"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainStoneType3 = "";
						return;
					}
					
					if (mainStoneType3 == "dark")
					{
						txtDescDark.HideWindow();
						txtDescHoly.ShowWindow();
						txtDescHoly.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 194);
					}
					else if (mainStoneType3 == "holy")
					{
						txtDescHoly.HideWindow();
						txtDescDark.ShowWindow();
						txtDescDark.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 194);
					}
					else
					{
						MessageBox("Not dark/holy");
						mainStoneType3 = "";
						return;
					}
				}
				
				if (!isMainCrystalChosen3)
				{
					txtGuide.SetText("Enchant with stones");
					armorEnchantOption3 = 0;
				}
				else
				{
					txtGuide.SetText("Enchant with stones + crystals");
					armorEnchantOption3 = 2;
				}
				
				bgMainStone3.HideWindow();
				itemMainStone3.Clear();
				itemMainStone3.AddItem(a_ItemInfo);
				stoneInfo3 = a_ItemInfo;
				curStoneCount3 = CalcStonesAtt(a_ItemInfo.ID.ClassID, "");
				txtStoneCount3.SetText( string( CalcStonesAtt(a_ItemInfo.ID.ClassID, "") ) );
				txtStoneCount3.ShowWindow();
				isMainStoneChosen3 = true;
				
				btnContinue.ShowWindow();
				btnBack.HideWindow();
				
				//sysDebug("On drop stone 3");
			}
		break;
		case "itemMainCrystal3":
			if (!itemMainCrystal3.IsEnableWindow())
			{
				return;
			}
			if (!isStone(a_ItemInfo) && isCrystal(a_ItemInfo))
			{
				
				if (InStr( Caps(a_ItemInfo.Name), Caps("Fire") ) > -1 )
				{
					mainCrystalType3 = "fire";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Water") ) > -1 )
				{
					mainCrystalType3 = "water";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Wind") ) > -1 )
				{
					mainCrystalType3 = "wind";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Earth") ) > -1 )
				{
					mainCrystalType3 = "earth";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Holy") ) > -1 )
				{
					mainCrystalType3 = "holy";
				}
				else if (InStr( Caps(a_ItemInfo.Name), Caps("Dark") ) > -1 )
				{
					mainCrystalType3 = "dark";
				}
				
				if (!isWeapon)
				{
					if (RequestArmorThirdAttValue() == 120)
					{
						//flagThirdStop = true;
						MessageBox("Third att is at maximum");
						mainCrystalType3 = "";
						return;
					}
					
					if (mainCrystalType3 == "dark" && ( armorFirstAtt == "dark" || armorSecondAtt == "dark" || armorThirdAtt == "dark"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainCrystalType3 = "";
						return;
					}
					else if (mainCrystalType3 == "holy" && ( armorFirstAtt == "holy" || armorSecondAtt == "holy" || armorThirdAtt == "holy"))
					{
						MessageBox("Opposite attribute is dropped!");
						mainCrystalType3 = "";
						return;
					}
					
					if (mainCrystalType3 == "dark")
					{
						txtDescDark.HideWindow();
						txtDescHoly.ShowWindow();
						txtDescHoly.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 194);
					}
					else if (mainCrystalType3 == "holy")
					{
						txtDescHoly.HideWindow();
						txtDescDark.ShowWindow();
						txtDescDark.SetAnchor("AutoAtt", "TopCenter", "TopCenter", 0, 194);
					}
					else
					{
						MessageBox("Not dark/holy");
						mainCrystalType3 = "";
						return;
					}
				}
				
				if (!isMainStoneChosen3)
				{
					txtGuide.SetText("Enchant with crystals");
					armorEnchantOption3 = 1;
				}
				else
				{
					txtGuide.SetText("Enchant with stones + crystals");
					armorEnchantOption3 = 2;
				}
				
				bgMainCrystal3.HideWindow();
				itemMainCrystal3.Clear();
				itemMainCrystal3.AddItem(a_ItemInfo);
				crystalInfo3 = a_ItemInfo;
				curCrystalCount3 = CalcCrystalsAtt(a_ItemInfo.ID.ClassID, "");
				txtCrystalCount3.SetText( string( CalcCrystalsAtt(a_ItemInfo.ID.ClassID, "") ) );
				txtCrystalCount3.ShowWindow();
				isMainCrystalChosen3 = true;
				
				btnContinue.ShowWindow();
				btnBack.HideWindow();
				
				//sysDebug("On drop crystal 3");
			}
		break;
	}
}

function ClearItem()
{
	mainID = GetItemID(-1);
	itemMainWeapon.Clear();
	itemMainStone.Clear();
	itemMainStone2.Clear();
	itemMainStone3.Clear();
	itemMainCrystal.Clear();
	itemMainCrystal2.Clear();
	itemMainCrystal3.Clear();
	itemMainArmor.Clear();
	SetToDefault();
}

function bool isStone(ItemInfo info)
{
	if (info.ID.ClassID == 9546)
	{
		return true;
	}
	else if (info.ID.ClassID == 9547)
	{
		return true;
	}
	else if (info.ID.ClassID == 9548)
	{
		return true;
	}
	else if (info.ID.ClassID == 9549)
	{
		return true;
	}
	else if (info.ID.ClassID == 9550)
	{
		return true;
	}
	else if (info.ID.ClassID == 9551)
	{
		return true;
	}
	
	//sysDebug("NOT STONE");
	return false;
}

function bool isCrystal(ItemInfo info)
{
	if (info.ID.ClassID == 9552)
	{
		return true;
	}
	else if (info.ID.ClassID == 9553)
	{
		return true;
	}
	else if (info.ID.ClassID == 9554)
	{
		return true;
	}
	else if (info.ID.ClassID == 9555)
	{
		return true;
	}
	else if (info.ID.ClassID == 9556)
	{
		return true;
	}
	else if (info.ID.ClassID == 9557)
	{
		return true;
	}
	
	//sysDebug("NOT CRYSTAL");
	return false;
}

function string ReverseATT(string type)
{
	local string Result;
	
	switch (type)
	{
		case "fire":
			Result = "water";
		break;
		case "water":
			Result = "fire";
		break;
		case "earth":
			Result = "wind";
		break;
		case "wind":
			Result = "earth";
		break;
		case "dark":
			Result = "holy";
		break;
		case "holy":
			Result = "dark";
		break;
	}
	
	return Result;
}

function int GetAttValue(ItemInfo info, string attType)
{
	switch(attType)
	{
		case "fire":
			return info.DefenseAttributeValueFire;
		break;
		case "water":
			return info.DefenseAttributeValueWater;
		break;
		case "wind":
			return info.DefenseAttributeValueWind;
		break;
		case "earth":
			return info.DefenseAttributeValueEarth;
		break;
		case "holy":
			return info.DefenseAttributeValueHoly;
		break;
		case "dark":
			return info.DefenseAttributeValueUnholy;
		break;
	}
}

function int AddCurAttValue(string attNum, int value)
{
	switch (attNum)
	{
		case "fire":
			return AttType[0] += value;
		break;
		case "water":
			return AttType[1] += value;
		break;
		case "earth":
			return AttType[2] += value;
		break;
		case "wind":
			return AttType[3] += value;
		break;
		case "dark":
			return AttType[4] += value;
		break;
		case "holy":
			return AttType[5] += value;
		break;
	}
}

function int RequestArmorFirstAttValue()
{
	switch (armorFirstAtt)
	{
		case "fire":
			return AttType[0];
		break;
		case "water":
			return AttType[1];
		break;
		case "earth":
			return AttType[2];
		break;
		case "wind":
			return AttType[3];
		break;
		case "dark":
			return AttType[4];
		break;
		case "holy":
			return AttType[5];
		break;
		default:
			return 0;
		break;
	}
}

function int RequestArmorSecondAttValue()
{
	switch (armorSecondAtt)
	{
		case "earth":
			return AttType[2];
		break;
		case "wind":
			return AttType[3];
		break;
		case "dark":
			return AttType[4];
		break;
		case "holy":
			return AttType[5];
		break;
		default:
			return 0;
		break;
	}
}

function int RequestArmorThirdAttValue()
{
	switch (armorThirdAtt)
	{
		case "dark":
			return AttType[4];
		break;
		case "holy":
			return AttType[5];
		break;
		default:
			return 0;
		break;
	}
}

function string GetWeaponAttTypeByID(int attType)
{
	switch(attType)
	{
		case 0:
			return "fire";
		break;
		case 1:
			return "water";
		break;
		case 2:
			return "wind";
		break;
		case 3:
			return "earth";
		break;
		case 4:
			return "holy";
		break;
		case 5:
			return "dark";
		break;
		default:
			return "";
		break;
	}
}

function SetBarTextures(BarHandle handle, string type)
{
	switch(type)
	{
		case "fire":
			handle.SetTexture(0, "l2ui_ct1.Gauge_DF_Attribute_Fire_Left");
			handle.SetTexture(1, "l2ui_ct1.Gauge_DF_Attribute_Fire_Center");
			handle.SetTexture(2, "l2ui_ct1.Gauge_DF_Attribute_Fire_Right");
			handle.SetTexture(3, "l2ui_ct1.Gauge_DF_Attribute_Fire_BG_Left");
			handle.SetTexture(4, "l2ui_ct1.Gauge_DF_Attribute_Fire_BG_Center");
			handle.SetTexture(5, "l2ui_ct1.Gauge_DF_Attribute_Fire_BG_Right");
		break;
		case "water":
			handle.SetTexture(0, "l2ui_ct1.Gauge_DF_Attribute_Water_Left");
			handle.SetTexture(1, "l2ui_ct1.Gauge_DF_Attribute_Water_Center");
			handle.SetTexture(2, "l2ui_ct1.Gauge_DF_Attribute_Water_Right");
			handle.SetTexture(3, "l2ui_ct1.Gauge_DF_Attribute_Water_BG_Left");
			handle.SetTexture(4, "l2ui_ct1.Gauge_DF_Attribute_Water_BG_Center");
			handle.SetTexture(5, "l2ui_ct1.Gauge_DF_Attribute_Water_BG_Right");
		break;
		case "earth":
			handle.SetTexture(0, "l2ui_ct1.Gauge_DF_Attribute_Earth_Left");
			handle.SetTexture(1, "l2ui_ct1.Gauge_DF_Attribute_Earth_Center");
			handle.SetTexture(2, "l2ui_ct1.Gauge_DF_Attribute_Earth_Right");
			handle.SetTexture(3, "l2ui_ct1.Gauge_DF_Attribute_Earth_BG_Left");
			handle.SetTexture(4, "l2ui_ct1.Gauge_DF_Attribute_Earth_BG_Center");
			handle.SetTexture(5, "l2ui_ct1.Gauge_DF_Attribute_Earth_BG_Right");
		break;
		case "wind":
			handle.SetTexture(0, "l2ui_ct1.Gauge_DF_Attribute_Wind_Left");
			handle.SetTexture(1, "l2ui_ct1.Gauge_DF_Attribute_Wind_Center");
			handle.SetTexture(2, "l2ui_ct1.Gauge_DF_Attribute_Wind_Right");
			handle.SetTexture(3, "l2ui_ct1.Gauge_DF_Attribute_Wind_BG_Left");
			handle.SetTexture(4, "l2ui_ct1.Gauge_DF_Attribute_Wind_BG_Center");
			handle.SetTexture(5, "l2ui_ct1.Gauge_DF_Attribute_Wind_BG_Right");
		break;
		case "dark":
			handle.SetTexture(0, "l2ui_ct1.Gauge_DF_Attribute_Dark_Left");
			handle.SetTexture(1, "l2ui_ct1.Gauge_DF_Attribute_Dark_Center");
			handle.SetTexture(2, "l2ui_ct1.Gauge_DF_Attribute_Dark_Right");
			handle.SetTexture(3, "l2ui_ct1.Gauge_DF_Attribute_Dark_BG_Left");
			handle.SetTexture(4, "l2ui_ct1.Gauge_DF_Attribute_Dark_BG_Center");
			handle.SetTexture(5, "l2ui_ct1.Gauge_DF_Attribute_Dark_BG_Right");
		break;
		case "holy":
			handle.SetTexture(0, "l2ui_ct1.Gauge_DF_Attribute_Divine_Left");
			handle.SetTexture(1, "l2ui_ct1.Gauge_DF_Attribute_Divine_Center");
			handle.SetTexture(2, "l2ui_ct1.Gauge_DF_Attribute_Divine_Right");
			handle.SetTexture(3, "l2ui_ct1.Gauge_DF_Attribute_Divine_BG_Left");
			handle.SetTexture(4, "l2ui_ct1.Gauge_DF_Attribute_Divine_BG_Center");
			handle.SetTexture(5, "l2ui_ct1.Gauge_DF_Attribute_Divine_BG_Right");
		break;
	}
}

function int GetDynamicArmorAttValue()
{
	local string type1;
	local string type2;
	local string type3;
	
	local int value;
	
	if (mainStoneType != "")
	{
		type1 = mainStoneType;
	} else if (mainCrystalType != "")
	{
		type1 = mainCrystalType;
	}
	
	if (mainStoneType2 != "")
	{
		type2 = mainStoneType2;
	} else if (mainCrystalType2 != "")
	{
		type2 = mainCrystalType2;
	}
	
	if (mainStoneType3 != "")
	{
		type3 = mainStoneType3;
	} else if (mainCrystalType3 != "")
	{
		type3 = mainCrystalType3;
	}
	
	if (!flagFirstStop)
	{
		value = GetAttValue(mainInfo, ReverseATT(type1));
		return value;
	} else if (!flagSecondStop)
	{
		value = GetAttValue(mainInfo, ReverseATT(type2));
		return value;
	} else if (!flagThirdStop)
	{
		value = GetAttValue(mainInfo, ReverseATT(type3));
		return value;
	}
	else
	{
		return -1;
	}
}

function int GetSpeedFromSliderTick(int iTick)
{
	local int ReturnSpeed;
	switch(iTick)
	{
	case 0 :
		ReturnSpeed = 350;
		break;
	case 1 :
		ReturnSpeed = 500;
		break;
	case 2 :
		ReturnSpeed = 750;
		break;
	case 3 :
		ReturnSpeed = 1000;
		break;
	case 4 :
		ReturnSpeed = 2000;
		break;
	}

	return ReturnSpeed;
}

function OnModifyCurrentTickSliderCtrl(string strID, int iCurrentTick)
{
	local int Speed;
	Speed = GetSpeedFromSliderTick(iCurrentTick);
	switch(strID)
	{
	case "sliderSpeed" :
		EnSpeed = Speed;
		txtGuide.SetText(EnSpeed @ "ms");
		break;
	}
}

defaultproperties
{
	
}