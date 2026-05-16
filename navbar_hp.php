<?php
// Mendeteksi halaman yang sedang aktif (tanpa ekstensi .php)
$current_page = basename($_SERVER['PHP_SELF'], ".php");
// Jika diakses dari root (domain.com/), set default ke index
if ($current_page == '' || $current_page == 'index.php') {
    $current_page = 'index';
}
?>
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 flex justify-around items-center pb-2 pt-2 px-2 z-[90] rounded-t-3xl shadow-[0_-10px_40px_rgba(0,0,0,0.06)]">
    
    <a href="index" class="flex flex-col items-center p-2 <?= ($current_page == 'index') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-600' ?> transition-colors w-16 group">
        <i class="fas fa-home text-lg mb-1 <?= ($current_page != 'index') ? 'group-hover:scale-110 transition-transform' : '' ?>"></i>
        <span class="text-[9px] font-bold">Beranda</span>
    </a>
    
    <a href="riwayat" class="flex flex-col items-center p-2 <?= ($current_page == 'riwayat') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500' ?> transition-colors w-16 group">
        <i class="fas fa-receipt text-lg mb-1 <?= ($current_page != 'riwayat') ? 'group-hover:scale-110 transition-transform' : '' ?>"></i>
        <span class="text-[9px] font-bold">Riwayat</span>
    </a>
    
    <div class="relative -top-5 shrink-0">
        <button onclick="openActionMenu()" class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center text-xl shadow-[0_8px_20px_rgba(37,99,235,0.4)] border-4 border-white transform transition-transform active:scale-95">
            <i class="fas fa-plus"></i>
        </button>
    </div>
    
    <a href="hutang" class="flex flex-col items-center p-2 <?= ($current_page == 'hutang') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500' ?> transition-colors w-16 group">
        <i class="fas fa-file-invoice-dollar text-lg mb-1 <?= ($current_page != 'hutang') ? 'group-hover:scale-110 transition-transform' : '' ?>"></i>
        <span class="text-[9px] font-bold">Hutang</span>
    </a>
    
    <a href="berulang" class="flex flex-col items-center p-2 <?= ($current_page == 'berulang') ? 'text-blue-600' : 'text-gray-400 hover:text-blue-500' ?> transition-colors w-16 group">
        <i class="fas fa-sync text-lg mb-1 <?= ($current_page != 'berulang') ? 'group-hover:scale-110 transition-transform' : '' ?>"></i>
        <span class="text-[9px] font-bold">Langganan</span>
    </a>
    
</div>