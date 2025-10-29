from PIL import Image, ImageOps
import os
import re

def resize_images(folder_path):
    subfolder_path = os.path.join(folder_path, "scaled")
    os.makedirs(subfolder_path, exist_ok=True)

    for filename in os.listdir(folder_path):
        if filename.lower().endswith(('.jpg', '.jpeg', '.png', '.gif')):
            print(filename)
            image_path = os.path.join(folder_path, filename)
            image = Image.open(image_path)

            # 1. Transpose first (EXIF orientation fix)
            image = ImageOps.exif_transpose(image)

            # 2. Resize and crop to 500x500
            image_cropped = ImageOps.fit(image, (500, 500), method=Image.Resampling.LANCZOS)

            # 3. create new file name
            name, ext = os.path.splitext(filename)
            # example: "MaxMustermann" -> "max_mustermann"
            clean_name = re.sub(r'(?<!^)(?=[A-Z])', '_', name).lower()

            new_filename = f"{clean_name}{ext.lower()}"
            new_image_path = os.path.join(subfolder_path, new_filename)

            image_cropped.save(new_image_path)
            print(f"Saved: {new_filename}")
        else:
            print(f"WARN: Skipping non-image file: {filename}")

resize_images("./new")
