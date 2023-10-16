<!DOCTYPE html>

<html lang="en">

<head>
  <title>Upload test</title>

</head>

<body>
<form action="/upload" method="POST" enctype="multipart/form-data">
  @csrf
  <input type="file" name="file" id="file">
  <button type="submit">Upload File</button>
</form>


</body>

</html>
