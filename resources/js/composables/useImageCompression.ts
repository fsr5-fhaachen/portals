/**
 * Compression of images in the browser before they are uploaded. The image is scaled down to the maximum size
 * and stored as jpeg. Animated gifs and files the browser can not read are uploaded unchanged.
 */
export default function () {
  const compressImage = async (
    file: File,
    maxSize = 1200,
    quality = 0.8,
  ): Promise<File> => {
    if (!file.type.startsWith("image/") || file.type === "image/gif") {
      return file;
    }

    const bitmap = await createImageBitmap(file, {
      imageOrientation: "from-image",
    }).catch(() => null);
    if (!bitmap) {
      return file;
    }

    const scale = Math.min(1, maxSize / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement("canvas");
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);

    const context = canvas.getContext("2d");
    if (!context) {
      bitmap.close();
      return file;
    }

    // jpeg has no transparency, so transparent parts of a png get a white background instead of a black one
    context.fillStyle = "#ffffff";
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    const blob = await new Promise<Blob | null>((resolve) =>
      canvas.toBlob(resolve, "image/jpeg", quality),
    );

    // keep the original if the compression does not make the file smaller
    if (!blob || blob.size >= file.size) {
      return file;
    }

    return new File([blob], file.name.replace(/\.[^.]+$/, "") + ".jpg", {
      type: "image/jpeg",
    });
  };

  return {
    compressImage,
  };
}
