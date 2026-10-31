package l2f.gameserver.dao;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import l2f.commons.dao.JdbcEntityState;
import l2f.gameserver.database.DatabaseFactory;
import l2f.gameserver.model.entity.residence.Castle;

public class CastleDAO
{
	private static final Logger _log = LoggerFactory.getLogger(CastleDAO.class);

	private static final CastleDAO _instance = new CastleDAO();

	/*
	 * ============================================================
	 * CARREGA OS DADOS DO CASTELO
	 * ============================================================
	 *
	 * collected_shops foi adicionado para que a arrecadação
	 * das lojas não seja perdida quando o GameServer reiniciar.
	 */
	public static final String SELECT_SQL_QUERY =
			"SELECT tax_percent, treasury, collected_shops, reward_count, " +
			"siege_date, last_siege_date, own_date " +
			"FROM castle WHERE id=? LIMIT 1";

	/*
	 * ============================================================
	 * SALVA OS DADOS DO CASTELO
	 * ============================================================
	 */
	public static final String UPDATE_SQL_QUERY =
			"UPDATE castle SET " +
			"tax_percent=?, " +
			"treasury=?, " +
			"collected_shops=?, " +
			"reward_count=?, " +
			"siege_date=?, " +
			"last_siege_date=?, " +
			"own_date=? " +
			"WHERE id=?";

	public static CastleDAO getInstance()
	{
		return _instance;
	}

	/*
	 * ============================================================
	 * SELECT
	 * ============================================================
	 */
	public void select(Castle castle)
	{
		try (Connection con = DatabaseFactory.getInstance().getConnection();
				PreparedStatement statement = con.prepareStatement(SELECT_SQL_QUERY))
		{
			statement.setInt(1, castle.getId());

			try (ResultSet rset = statement.executeQuery())
			{
				if (rset.next())
				{
					castle.setTaxPercent(
							rset.getInt("tax_percent"));

					castle.setTreasury(
							rset.getLong("treasury"));

					/*
					 * Carrega a arrecadação das lojas.
					 */
					castle.setCollectedShops(
							rset.getLong("collected_shops"));

					castle.setRewardCount(
							rset.getInt("reward_count"));

					castle.getSiegeDate().setTimeInMillis(
							rset.getLong("siege_date"));

					castle.getLastSiegeDate().setTimeInMillis(
							rset.getLong("last_siege_date"));

					castle.getOwnDate().setTimeInMillis(
							rset.getLong("own_date"));
				}
			}
		}
		catch (SQLException e)
		{
			_log.error(
					"CastleDAO.select(Castle):",
					e);
		}
	}

	/*
	 * ============================================================
	 * UPDATE
	 * ============================================================
	 */
	public void update(Castle residence)
	{
		if (!residence.getJdbcState().isUpdatable())
			return;

		residence.setJdbcState(
				JdbcEntityState.STORED);

		update0(residence);
	}

	/*
	 * ============================================================
	 * UPDATE INTERNO
	 * ============================================================
	 */
	private void update0(Castle castle)
	{
		try (Connection con = DatabaseFactory.getInstance().getConnection();
				PreparedStatement statement = con.prepareStatement(UPDATE_SQL_QUERY))
		{
			/*
			 * 1 - Taxa
			 */
			statement.setInt(
					1,
					castle.getTaxPercent0());

			/*
			 * 2 - Tesouro principal
			 */
			statement.setLong(
					2,
					castle.getTreasury());

			/*
			 * 3 - Arrecadação das lojas
			 */
			statement.setLong(
					3,
					castle.getCollectedShops());

			/*
			 * 4 - Reward count
			 */
			statement.setInt(
					4,
					castle.getRewardCount());

			/*
			 * 5 - Siege date
			 */
			statement.setLong(
					5,
					castle.getSiegeDate().getTimeInMillis());

			/*
			 * 6 - Last siege date
			 */
			statement.setLong(
					6,
					castle.getLastSiegeDate().getTimeInMillis());

			/*
			 * 7 - Own date
			 */
			statement.setLong(
					7,
					castle.getOwnDate().getTimeInMillis());

			/*
			 * 8 - Castle ID
			 */
			statement.setInt(
					8,
					castle.getId());

			statement.execute();
		}
		catch (SQLException e)
		{
			_log.warn(
					"CastleDAO#update0(Castle): ",
					e);
		}
	}
}