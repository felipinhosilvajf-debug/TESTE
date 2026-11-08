package org.krews.apollyon.incoming;

import com.eu.habbo.Emulator;
import com.eu.habbo.habbohotel.achievements.AchievementManager;
import com.eu.habbo.habbohotel.items.Item;
import com.eu.habbo.habbohotel.users.Habbo;
import com.eu.habbo.habbohotel.users.HabboItem;
import com.eu.habbo.messages.incoming.MessageHandler;
import com.eu.habbo.messages.outgoing.camera.CameraPurchaseOKMessageComposer;
import com.eu.habbo.messages.outgoing.catalog.NotEnoughBalanceMessageComposer;
import com.eu.habbo.messages.outgoing.inventory.FurniListAddOrUpdateComposer;
import com.eu.habbo.messages.outgoing.inventory.FurniListInvalidateComposer;
import com.eu.habbo.plugin.events.users.UserPurchasePictureEvent;
import gnu.trove.map.hash.THashMap;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

public class PurchasePhotoEvent extends MessageHandler {
    private static final Logger LOGGER = LoggerFactory.getLogger(PurchasePhotoEvent.class);
    public static int CAMERA_PURCHASE_CREDITS = Emulator.getConfig().getInt("camera.price.credits");
    public static int CAMERA_PURCHASE_POINTS = Emulator.getConfig().getInt("camera.price.points");
    public static int CAMERA_PURCHASE_POINTS_TYPE = Emulator.getConfig().getInt("camera.price.points.type");
    public THashMap<Habbo, Integer> lastRanTimestamps = new THashMap<Habbo, Integer>();
    public int getRatelimit() {
        return Emulator.getConfig().getInt("apollyon.cooldown.amount");
    }

    @Override
    public void handle() {
        if (this.client.getHabbo().getHabboInfo().getCredits() < PurchasePhotoEvent.CAMERA_PURCHASE_CREDITS) {
            this.client.sendResponse(new NotEnoughBalanceMessageComposer(true, false, 0));
            return;
        }

        if (this.client.getHabbo().getHabboInfo().getCurrencyAmount(PurchasePhotoEvent.CAMERA_PURCHASE_POINTS_TYPE) < PurchasePhotoEvent.CAMERA_PURCHASE_POINTS) {
            this.client.sendResponse(new NotEnoughBalanceMessageComposer(false, true, PurchasePhotoEvent.CAMERA_PURCHASE_POINTS_TYPE));
            return;
        }

        if (this.client.getHabbo().getHabboInfo().getPhotoTimestamp() == 0) return;
        if (this.client.getHabbo().getHabboInfo().getPhotoJSON().isEmpty()) return;
        if (!this.client.getHabbo().getHabboInfo().getPhotoJSON().contains(this.client.getHabbo().getHabboInfo().getPhotoTimestamp() + ""))
            return;

        if (Emulator.getPluginManager().fireEvent(new UserPurchasePictureEvent(this.client.getHabbo(), this.client.getHabbo().getHabboInfo().getPhotoURL(), this.client.getHabbo().getHabboInfo().getCurrentRoom().getId(), this.client.getHabbo().getHabboInfo().getPhotoTimestamp())).isCancelled()) {
            return;
        }

        int itemBaseId = Emulator.getConfig().getInt("camera.item_id");
        Item item = Emulator.getGameEnvironment().getItemManager().getItem(itemBaseId);

        // Check if the photo item does exist.
        if (item == null) {
            LOGGER.error("Failed to find photo item {} in database, unable to save photo.", itemBaseId);
            return;
        }

        // Check if the interaction type is corrct.
        String itemInteractionType = item.getInteractionType().getName();
        if (!itemInteractionType.equals("external_image")) {
            LOGGER.error("Photo item {} in has the wrong interaction type ({}), it needs to be external_image.", itemBaseId, itemInteractionType);
            return;
        }

        HabboItem photoItem = Emulator.getGameEnvironment().getItemManager().createItem(this.client.getHabbo().getHabboInfo().getId(), item, 0, 0, this.client.getHabbo().getHabboInfo().getPhotoJSON());

        if (photoItem != null) {
            photoItem.setExtradata(photoItem.getExtradata().replace("%id%", photoItem.getId() + ""));
            photoItem.needsUpdate(true);

            this.client.getHabbo().getInventory().getItemsComponent().addItem(photoItem);

            this.client.sendResponse(new CameraPurchaseOKMessageComposer());
            this.client.sendResponse(new FurniListAddOrUpdateComposer(photoItem));
            this.client.sendResponse(new FurniListInvalidateComposer());
            this.client.getHabbo().giveCredits(-PurchasePhotoEvent.CAMERA_PURCHASE_CREDITS);
            this.client.getHabbo().givePoints(PurchasePhotoEvent.CAMERA_PURCHASE_POINTS_TYPE, -PurchasePhotoEvent.CAMERA_PURCHASE_POINTS);

            AchievementManager.progressAchievement(this.client.getHabbo(), Emulator.getGameEnvironment().getAchievementManager().getAchievement("CameraPhotoCount"));
        }
    }
}