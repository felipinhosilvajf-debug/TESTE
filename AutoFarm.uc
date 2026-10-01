class AutoFarm extends UICommonAPI;

var WindowHandle Me;

var ItemWindowHandle hSkill1;
var ItemWindowHandle hSkill2;
var ItemWindowHandle hSkill3;

function OnLoad()
{
	Me = GetWindowHandle("AutoFarm");

	hSkill1 = GetItemWindowHandle("AutoFarm.Skill1");
	hSkill2 = GetItemWindowHandle("AutoFarm.Skill2");
	hSkill3 = GetItemWindowHandle("AutoFarm.Skill3");
}

function OnShow()
{
	Me.ShowWindow();
	Me.SetFocus();
}

function OnDropItem(String a_WindowID, ItemInfo a_ItemInfo, int X, int Y)
{
	switch (a_WindowID)
	{
		case "Skill1":
			hSkill1.Clear();
			hSkill1.AddItem(a_ItemInfo);
		break;

		case "Skill2":
			hSkill2.Clear();
			hSkill2.AddItem(a_ItemInfo);
		break;

		case "Skill3":
			hSkill3.Clear();
			hSkill3.AddItem(a_ItemInfo);
		break;
	}
}

function OnRClickItem(String strID, int index)
{
	switch (strID)
	{
		case "Skill1":
			hSkill1.Clear();
		break;

		case "Skill2":
			hSkill2.Clear();
		break;

		case "Skill3":
			hSkill3.Clear();
		break;
	}
}

function ShowAutoFarm()
{
	Me.ShowWindow();
	Me.SetFocus();
}

function OpenAutoFarm()
{
	class'UIAPI_WINDOW'.static.ShowWindow("AutoFarm");
	class'UIAPI_WINDOW'.static.SetFocus("AutoFarm");
}
