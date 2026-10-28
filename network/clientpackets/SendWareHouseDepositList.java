package l2f.gameserver.network.clientpackets;

import l2f.commons.math.SafeMath;
import l2f.gameserver.Config;
import l2f.gameserver.handler.bbs.CommunityBoardManager;
import l2f.gameserver.model.Player;
import l2f.gameserver.model.items.ItemInstance;
import l2f.gameserver.model.items.PcInventory;
import l2f.gameserver.model.items.Warehouse;
import l2f.gameserver.model.items.Warehouse.WarehouseType;
import l2f.gameserver.network.serverpackets.components.SystemMsg;
import l2f.gameserver.templates.item.ItemTemplate;
import org.apache.commons.lang3.ArrayUtils;

/**
 * Format: cdb, b - array of (dd)
 */
public class SendWareHouseDepositList extends L2GameClientPacket
{
	private static final long _WAREHOUSE_FEE = 30; // Taxa fixa do Warehouse
	private static final int _ADENA_TAX_PERCENT = 10; // Taxa de 10% sobre Adena

	private int _count;
	private int[] _items;
	private long[] _itemQ;

	@Override
	protected void readImpl()
	{
		_count = readD();
		if (_count * 12 > _buf.remaining() || _count > Short.MAX_VALUE || _count < 1)
		{
			_count = 0;
			return;
		}

		_items = new int[_count];
		_itemQ = new long[_count];

		for (int i = 0; i < _count; i++)
		{
			_items[i] = readD();
			_itemQ[i] = readQ();

			if (_itemQ[i] < 1 || ArrayUtils.indexOf(_items, _items[i]) < i)
			{
				_count = 0;
				return;
			}
		}
	}

	@Override
	protected void runImpl()
	{
		Player activeChar = getClient().getActiveChar();

		if (activeChar == null || _count == 0)
			return;

		if (!activeChar.getPlayerAccess().UseWarehouse)
		{
			activeChar.sendActionFailed();
			return;
		}

		if (activeChar.isActionsDisabled())
		{
			activeChar.sendActionFailed();
			return;
		}

		if (activeChar.isInStoreMode())
		{
			activeChar.sendPacket(SystemMsg.WHILE_OPERATING_A_PRIVATE_STORE_OR_WORKSHOP_YOU_CANNOT_DISCARD_DESTROY_OR_TRADE_AN_ITEM);
			return;
		}

		if (activeChar.isInTrade())
		{
			activeChar.sendActionFailed();
			return;
		}

		if (activeChar.getUsingWarehouseType() == WarehouseType.FREIGHT)
		{
			checkAuctionAdd(activeChar, _items, _itemQ);
			return;
		}

		PcInventory inventory = activeChar.getInventory();

		boolean privatewh = activeChar.getUsingWarehouseType() != WarehouseType.CLAN;

		Warehouse warehouse;

		if (privatewh)
			warehouse = activeChar.getWarehouse();
		else
			warehouse = activeChar.getClan().getWarehouse();

		inventory.writeLock();
		warehouse.writeLock();

		try
		{
			int slotsleft = 0;

			if (privatewh)
				slotsleft = activeChar.getWarehouseLimit() - warehouse.getSize();
			else
				slotsleft = activeChar.getClan().getWhBonus() + Config.WAREHOUSE_SLOTS_CLAN - warehouse.getSize();

			int items = 0;

			/*
			 * Validação dos itens que serão depositados.
			 */
			for (int i = 0; i < _count; i++)
			{
				ItemInstance item = inventory.getItemByObjectId(_items[i]);

				if (item == null || item.getCount() < _itemQ[i] || !item.canBeStored(activeChar, privatewh || !privatewh))
				{
					_items[i] = 0;
					_itemQ[i] = 0L;
					continue;
				}

				/*
				 * Verifica se o item precisa de um novo slot.
				 */
				if (!item.isStackable() || warehouse.getItemByItemId(item.getItemId()) == null)
				{
					if (slotsleft <= 0)
					{
						_items[i] = 0;
						_itemQ[i] = 0L;
						continue;
					}

					slotsleft--;
				}

				items++;
			}

			/*
			 * Se não houver espaço suficiente no Warehouse.
			 */
			if (slotsleft <= 0)
				activeChar.sendPacket(SystemMsg.YOUR_WAREHOUSE_IS_FULL);

			if (items == 0)
			{
				activeChar.sendPacket(SystemMsg.INCORRECT_ITEM_COUNT);
				return;
			}

			/*
			 * ============================================================
			 * TAXA DO WAREHOUSE
			 * ============================================================
			 *
			 * Cada item depositado continua pagando a taxa fixa de 30 Adena.
			 */
			long warehouseFee = SafeMath.mulAndCheck(items, _WAREHOUSE_FEE);

			/*
			 * ============================================================
			 * TAXA DE ADENA
			 * ============================================================
			 *
			 * Se houver Adena entre os itens depositados:
			 *
			 * Exemplo:
			 *
			 * 1.000.000 Adena
			 * 10% = 100.000
			 * Warehouse recebe = 900.000
			 *
			 * A taxa é retirada do próprio valor depositado.
			 */
			long adenaTax = 0;

			for (int i = 0; i < _count; i++)
			{
				if (_items[i] == 0)
					continue;

				ItemInstance item = inventory.getItemByObjectId(_items[i]);

				if (item == null)
					continue;

				if (item.getItemId() == ItemTemplate.ITEM_ID_ADENA)
				{
					long adenaAmount = _itemQ[i];

					// Calcula 10% da Adena depositada.
					adenaTax = SafeMath.mulAndCheck(adenaAmount, _ADENA_TAX_PERCENT) / 100;

					break;
				}
			}

			/*
			 * A taxa fixa do Warehouse é paga separadamente.
			 *
			 * A taxa de 10% da Adena faz parte do próprio valor
			 * depositado e não é cobrada novamente do jogador.
			 */
			long totalFee = SafeMath.addAndCheck(warehouseFee, adenaTax);

			/*
			 * Verificação de segurança:
			 *
			 * O jogador precisa ter:
			 *
			 * - o valor que será depositado;
			 * - a taxa fixa do Warehouse;
			 * - a taxa de 10% da Adena.
			 *
			 * Como a taxa de Adena será descontada antes da transferência,
			 * o valor líquido será depositado no Warehouse.
			 */
			long totalAdenaToRemove = 0;

			for (int i = 0; i < _count; i++)
			{
				if (_items[i] == 0)
					continue;

				ItemInstance item = inventory.getItemByObjectId(_items[i]);

				if (item == null)
					continue;

				if (item.getItemId() == ItemTemplate.ITEM_ID_ADENA)
				{
					/*
					 * O valor bruto informado pelo jogador é retirado
					 * do inventário, mas a parte correspondente à taxa
					 * já foi separada como adenaTax.
					 */
					totalAdenaToRemove = _itemQ[i];
					break;
				}
			}

			/*
			 * O jogador precisa possuir:
			 *
			 * valor da Adena depositada + taxa fixa do Warehouse.
			 *
			 * A adenaTax está dentro do valor bruto depositado,
			 * portanto não deve ser somada novamente aqui.
			 */
			long requiredAdena = SafeMath.addAndCheck(totalAdenaToRemove, warehouseFee);

			if (requiredAdena > activeChar.getAdena())
			{
				activeChar.sendPacket(SystemMsg.YOU_LACK_THE_FUNDS_NEEDED_TO_PAY_FOR_THIS_TRANSACTION);
				return;
			}

			/*
			 * Cobra somente a taxa fixa do Warehouse.
			 *
			 * A taxa de 10% já será descontada ao transferir
			 * somente 90% da Adena para o Warehouse.
			 */
			if (warehouseFee > 0)
			{
				if (!activeChar.reduceAdena(
						warehouseFee,
						true,
						(privatewh ? "Private" : "Clan") + "WarehouseDepositFee"))
				{
					activeChar.sendPacket(SystemMsg.YOU_DO_NOT_HAVE_ENOUGH_ADENA);
					return;
				}
			}

			/*
			 * ============================================================
			 * TRANSFERÊNCIA DOS ITENS
			 * ============================================================
			 */
			for (int i = 0; i < _count; i++)
			{
				if (_items[i] == 0)
					continue;

				ItemInstance item = inventory.getItemByObjectId(_items[i]);

				if (item == null)
					continue;

				long amountToDeposit = _itemQ[i];

				/*
				 * Adena:
				 *
				 * Deposita somente 90%.
				 *
				 * Exemplo:
				 * 1.000.000 -> 900.000
				 */
				if (item.getItemId() == ItemTemplate.ITEM_ID_ADENA)
				{
					long tax = SafeMath.mulAndCheck(amountToDeposit, _ADENA_TAX_PERCENT) / 100;

					amountToDeposit -= tax;

					/*
					 * Segurança para nunca tentar depositar valor negativo.
					 */
					if (amountToDeposit < 0)
						amountToDeposit = 0;
				}

				if (amountToDeposit > 0)
				{
					ItemInstance depositedItem = inventory.removeItemByObjectId(
							_items[i],
							amountToDeposit,
							(privatewh ? "Private" : "Clan") + "WarehouseDeposit");

					if (depositedItem != null)
						warehouse.addItem(depositedItem, null, null);
				}
			}
		}
		catch (ArithmeticException ae)
		{
			// TODO audit
			activeChar.sendPacket(SystemMsg.YOU_HAVE_EXCEEDED_THE_QUANTITY_THAT_CAN_BE_INPUTTED);
			return;
		}
		finally
		{
			warehouse.writeUnlock();
			inventory.writeUnlock();
		}

		/*
		 * Atualiza os parâmetros do personagem.
		 */
		activeChar.sendChanges();

		activeChar.sendPacket(SystemMsg.THE_TRANSACTION_IS_COMPLETE);
	}

	private void checkAuctionAdd(Player activeChar, int[] _items, long[] _itemQ)
	{
		if (_items.length != 1 || _itemQ[0] != 1)
		{
			activeChar.sendMessage("You can add just one item at the time!");
			return;
		}

		CommunityBoardManager.getInstance()
				.getCommunityHandler("_sendTimePrice_")
				.onBypassCommand(activeChar, "_sendTimePrice_" + _items[0]);
	}
}