package l2f.gameserver.network.serverpackets;

public class ExStopScenePlayer extends L2GameServerPacket
{
	@Override
	protected final void writeImpl()
	{
		writeEx(0xE7);
	}
}
