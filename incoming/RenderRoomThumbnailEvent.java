package org.krews.apollyon.incoming;

import com.eu.habbo.Emulator;
import com.eu.habbo.habbohotel.rooms.Room;
import com.eu.habbo.messages.incoming.MessageHandler;
import com.eu.habbo.messages.outgoing.camera.ThumbnailStatusMessageComposer;
import com.eu.habbo.messages.outgoing.generic.alerts.HabboBroadcastMessageComposer;
import io.netty.buffer.ByteBuf;
import io.netty.buffer.ByteBufInputStream;
import io.netty.util.ReferenceCountUtil;
import lombok.extern.slf4j.Slf4j;
import org.krews.apollyon.ftp.FTPUploadService;
import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;

@Slf4j
public class RenderRoomThumbnailEvent extends MessageHandler {
    @Override
    public void handle() {
        if (! this.client.getHabbo().hasPermission("acc_camera")) {
            this.client.sendResponse(new HabboBroadcastMessageComposer(Emulator.getTexts().getValue("camera.permission")));
            return;
        }

        if (!this.client.getHabbo().getHabboInfo().getCurrentRoom().isOwner(this.client.getHabbo()))
            return;

        Room room = this.client.getHabbo().getHabboInfo().getCurrentRoom();

        if (room == null)
            return;

        if (!room.isOwner(this.client.getHabbo()) && !this.client.getHabbo().hasPermission("acc_modtool_ticket_q"))
            return;

        final int count = this.packet.readInt();

        ByteBuf image = this.packet.getBuffer().readBytes(count);

        if(image == null)
            return;

        try {
            this.packet.readString();
            this.packet.readString();
            this.packet.readInt();
            this.packet.readInt();

            try {
                if(Emulator.getConfig().getInt("ftp.enabled") == 1) {
                    byte[] imageBytes = new byte[image.readableBytes()];
                    image.readBytes(imageBytes);
                    FTPUploadService.uploadImage(imageBytes, Emulator.getConfig().getValue("imager.location.output.thumbnail") + room.getId() + ".png");
                }
                else {
                    BufferedImage theImage = null;
                    theImage = ImageIO.read(new ByteBufInputStream(image));
                    ImageIO.write(theImage, "png", new File(Emulator.getConfig().getValue("imager.location.output.thumbnail") + room.getId() + ".png"));
                }
            } catch (IOException e) {
                e.printStackTrace();
            } catch (IllegalArgumentException e) {
                log.error("[Apollyon] You are using a Habbo.swf that has not been patched to work with Apollyon. Please read the read me on a guide to patching your swf, or download a prepatched one on our git at:");
                log.error("[Apollyon] https://git.krews.org/morningstar/apollyon");
            }

            this.client.sendResponse(new ThumbnailStatusMessageComposer());
        } finally {
            ReferenceCountUtil.release(image);
        }
    }
}