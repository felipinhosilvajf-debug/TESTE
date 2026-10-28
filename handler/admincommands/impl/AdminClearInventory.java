package l2f.gameserver.handler.admincommands.impl;

import l2f.gameserver.handler.admincommands.IAdminCommandHandler;
import l2f.gameserver.model.Player;
import l2f.gameserver.model.World;
import l2f.gameserver.model.items.ItemInstance;

public class AdminClearInventory implements IAdminCommandHandler
{
	private static enum Commands
	{
		admin_limparinv
	}

	@Override
	public boolean useAdminCommand(Enum comm, String[] wordList, String fullString, Player activeChar)
	{
		Commands command = (Commands) comm;

		// Somente GM/ADM que possui permissão de Kick
		if (!activeChar.getPlayerAccess().CanKick)
			return false;

		switch (command)
		{
			case admin_limparinv:
			{
				Player player;

				// Sem nome: usa o personagem selecionado
				if (wordList.length == 1)
				{
					if (activeChar.getTarget() == null || !activeChar.getTarget().isPlayer())
					{
						activeChar.sendMessage("Selecione um personagem ou informe o nome.");
						return false;
					}

					player = (Player) activeChar.getTarget();
				}
				else
				{
					// Com nome: procura o personagem online
					player = World.getPlayer(wordList[1]);

					if (player == null)
					{
						activeChar.sendMessage("Personagem " + wordList[1] + " não encontrado.");
						return false;
					}
				}

				int removed = 0;

				for (ItemInstance item : player.getInventory().getItems())
				{
					if (item == null)
						continue;

					// Não remove itens equipados
					if (item.isEquipped())
						continue;

					int itemId = item.getItemId();
					long count = item.getCount();

					if (count > 0)
					{
						if (player.getInventory().destroyItemByItemId(itemId, count, "AdminClearInventory"))
							removed++;
					}
				}

				activeChar.sendMessage(
					"Inventário de " + player.getName() + " limpo. "
					+ removed + " tipos de itens removidos."
				);

				player.sendMessage("Seu inventário foi limpo por um administrador.");

				break;
			}
		}

		return true;
	}

	@Override
	public Enum[] getAdminCommandEnum()
	{
		return Commands.values();
	}
}