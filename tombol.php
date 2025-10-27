<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookmark Favorit</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex justify-center items-center h-screen bg-gray-100">

  <!-- Tombol Bookmark -->
  <button id="bookmarkBtn"
    class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all">
    <!-- Ikon Bookmark -->
    <svg id="bookmarkIcon" xmlns="http://www.w3.org/2000/svg" 
      fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
      class="w-6 h-6">
      <path stroke-linecap="round" stroke-linejoin="round"
        d="M5.25 5.25v13.5L12 15l6.75 3.75V5.25A2.25 2.25 0 0016.5 3h-9A2.25 2.25 0 005.25 5.25z" />
    </svg>
    <span id="bookmarkText">Tambah ke Favorit</span>
  </button>

  <script>
    const btn = document.getElementById("bookmarkBtn");
    const icon = document.getElementById("bookmarkIcon");
    const text = document.getElementById("bookmarkText");

    let isFavorited = false;

    btn.addEventListener("click", () => {
      isFavorited = !isFavorited;

      if (isFavorited) {
        // Ubah ikon jadi bookmark penuh
        icon.setAttribute("fill", "currentColor");
        text.textContent = "Tersimpan";
        btn.classList.replace("bg-green-600", "bg-yellow-500");
        btn.classList.replace("hover:bg-green-700", "hover:bg-yellow-600");
      } else {
        // Kembalikan ke ikon kosong
        icon.setAttribute("fill", "none");
        text.textContent = "Tambah ke Favorit";
        btn.classList.replace("bg-yellow-500", "bg-green-600");
        btn.classList.replace("hover:bg-yellow-600", "hover:bg-green-700");
      }
    });
  </script>

</body>
</html>
