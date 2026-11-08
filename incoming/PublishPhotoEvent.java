package org.krews.apollyon.incoming;

import com.eu.habbo.Emulator;
import com.eu.habbo.messages.incoming.MessageHandler;
import com.eu.habbo.messages.outgoing.camera.CameraPublishStatusMessageComposer;
import com.eu.habbo.messages.outgoing.catalog.NotEnoughBalanceMessageComposer;
import com.eu.habbo.messages.outgoing.generic.alerts.HabboBroadcastMessageComposer;
import com.eu.habbo.plugin.events.users.UserPublishPictureEvent;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

public class PublishPhotoEvent extends MessageHandler {
    @Override
    public void handle() {
        if (this.client.getHabbo().getHabboInfo().getCurrencyAmount(Emulator.getConfig().getInt("apollyon.publish.points.type")) < Emulator.getConfig().getInt("camera.price.points.publish")) {
            this.client.sendResponse(new NotEnoughBalanceMessageComposer(false, true, Emulator.getConfig().getInt("apollyon.publish.points.type")));
            this.client.sendResponse(new HabboBroadcastMessageComposer(Emulator.getTexts().getValue("apollyon.publish.points.not_enough")));
            return;
        }
        if (this.client.getHabbo().getHabboInfo().getPhotoTimestamp() != 0) {
            if (!this.client.getHabbo().getHabboInfo().getPhotoJSON().isEmpty()) {
                if (this.client.getHabbo().getHabboInfo().getPhotoJSON().contains(this.client.getHabbo().getHabboInfo().getPhotoTimestamp() + "")) {
                    int timestamp = Emulator.getIntUnixTimestamp();
                    boolean published = false;
                    int timeDiff = timestamp - this.client.getHabbo().getHabboInfo().getWebPublishTimestamp();
                    int wait = 0;
                    if (timeDiff < Emulator.getConfig().getInt("camera.publish.delay")) {
                        wait = timeDiff - Emulator.getConfig().getInt("camera.publish.delay");
                    }
                    else {
                        UserPublishPictureEvent publishPictureEvent = new UserPublishPictureEvent(this.client.getHabbo(), this.client.getHabbo().getHabboInfo().getPhotoURL(), timestamp, this.client.getHabbo().getHabboInfo().getPhotoRoomId());
                        if (!Emulator.getPluginManager().fireEvent(publishPictureEvent).isCancelled()) {
                            try (Connection connection = Emulator.getDatabase().getDataSource().getConnection(); PreparedStatement statement = connection.prepareStatement("INSERT INTO camera_web (user_id, room_id, timestamp, url) VALUES (?, ?, ?, ?)")) {
                                statement.setInt(1, this.client.getHabbo().getHabboInfo().getId());
                                statement.setInt(2, publishPictureEvent.getRoomId());
                                statement.setInt(3, publishPictureEvent.getTimestamp());
                                statement.setString(4, publishPictureEvent.getURL());
                                statement.execute();
                                this.client.getHabbo().getHabboInfo().setWebPublishTimestamp(timestamp);
                                this.client.getHabbo().givePoints(Emulator.getConfig().getInt("apollyon.publish.points.type"), -Emulator.getConfig().getInt("camera.price.points.publish"));
                                published = true;
                            } catch (SQLException throwables) {
                                throwables.printStackTrace();
                            }

                        }
                        else {
                            return;
                        }
                    }

                    this.client.sendResponse(new CameraPublishStatusMessageComposer(published, wait, published ? this.client.getHabbo().getHabboInfo().getPhotoURL() : ""));
                }
            }
        }
    }
}