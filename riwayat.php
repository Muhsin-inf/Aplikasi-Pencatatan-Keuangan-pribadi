<?php
require_once 'api/config.php';

// Jika tidak ada sesi user_id, tendang kembali ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F4F7FE; } </style>
</head>
<body class="antialiased text-gray-800 pb-20 md:pb-0">

    <?php include 'navbar.php'; ?>
    

    <main class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 bg-white p-6 rounded-[24px] shadow-sm border border-gray-100">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">Riwayat Transaksi</h1>
                <p class="text-gray-500 font-medium mt-1">Pantau arus kas Anda secara detail.</p>
            </div>
            
            <!-- Filter Dropdown -->
            <div class="flex items-center gap-2">
                <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 flex items-center">
                    <i class="fas fa-calendar text-gray-400 mr-2"></i>
                    <select id="historyFilter" onchange="fetchHistory()" class="bg-transparent font-bold text-gray-700 outline-none cursor-pointer">
                        <option value="this_month">Bulan Ini</option>
                        <option value="all">Semua Waktu</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Wadah List Riwayat -->
        <div id="historyContainer" class="space-y-6">
            <p class="text-center text-gray-400 py-10"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...</p>
        </div>

    </main>

<?php include 'navbar_hp.php'; ?>

    <script>
        const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
        
        // Format tanggal (contoh: 15 Agustus 2026)
        const formatDate = (dateStr) => {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateStr).toLocaleDateString('id-ID', options);
        };

        function fetchHistory() {
            const filterValue = document.getElementById('historyFilter').value;
            const container = document.getElementById('historyContainer');
            
            container.innerHTML = '<p class="text-center text-gray-400 py-10"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...</p>';

            fetch(`api/transaction.php?filter=${filterValue}`)
                .then(res => res.json())
                .then(data => {
                    container.innerHTML = '';
                    
                    if (data.status === 'success' && data.data.length > 0) {
                        // 1. KELOMPOKKAN DATA BERDASARKAN TANGGAL
                        const groupedData = {};
                        data.data.forEach(tx => {
                            if (!groupedData[tx.date]) groupedData[tx.date] = [];
                            groupedData[tx.date].push(tx);
                        });

                        // 2. RENDER HTML PER KELOMPOK TANGGAL
                        for (const [date, transactions] of Object.entries(groupedData)) {
                            
                            // Header Tanggal
                            let groupHtml = `
                                <div class="mb-6">
                                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3 pl-2">
                                        <i class="fas fa-calendar-day mr-2"></i>${formatDate(date)}
                                    </h3>
                                    <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                            `;

                            // Looping isi transaksi di tanggal tersebut
                            // Looping isi transaksi di tanggal tersebut
                            transactions.forEach((tx, index) => {
                                const isExpense = tx.type === 'expense';
                                const amountColor = isExpense ? 'text-red-500' : 'text-green-500';
                                const amountPrefix = isExpense ? '- ' : '+ ';
                                const borderBottom = index !== transactions.length - 1 ? 'border-b border-gray-50' : '';
                                
                                // Deteksi icon gambar atau fontawesome
                                const safeIconName = tx.icon_name ? tx.icon_name : 'wallet';
                                const isImage = safeIconName.includes('.');
                                
                                // Ikon disesuaikan ukurannya (lebih kecil di HP)
                                const iconHtml = isImage 
                                    ? `<img src="${safeIconName}" class="w-5 h-5 md:w-6 md:h-6 object-contain" alt="Icon" onerror="this.style.display='none'">`
                                    : `<i class="fas fa-${safeIconName} text-white text-sm md:text-base"></i>`;

                                // Catatan disesuaikan agar tidak mendesak ke kanan
                                const noteHtml = tx.note ? `<p class="text-[10px] md:text-xs text-gray-400 mt-0.5 truncate w-full">${tx.note}</p>` : '';

                                const dateObj = new Date(tx.created_at.replace(' ', 'T'));
                                const timeString = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                                groupHtml += `
                                    <div class="flex items-center justify-between p-3 md:p-5 hover:bg-gray-50/50 transition-colors ${borderBottom}">
                                        
                                        <!-- Kiri: Ikon & Info -->
                                        <!-- KUNCI RESPONSIVE: flex-1 min-w-0 memastikan kolom kiri bisa menyusut -->
                                        <div class="flex items-center gap-3 md:gap-4 flex-1 min-w-0">
                                            
                                            <!-- Ukuran Kotak Ikon Diperkecil di HP (w-10 h-10) -->
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl flex items-center justify-center shadow-inner shrink-0" style="background-color: ${tx.color}">
                                                ${iconHtml}
                                            </div>
                                            
                                            <div class="flex flex-col flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-1.5 md:gap-2">
                                                    <p class="font-extrabold text-gray-800 text-xs md:text-base truncate max-w-full">${tx.category_name}</p>
                                                    <span class="text-[9px] md:text-[10px] text-gray-400 font-bold bg-gray-50 px-1.5 py-0.5 rounded flex items-center gap-1 shrink-0">
                                                        <i class="far fa-clock"></i> ${timeString}
                                                    </span>
                                                </div>
                                                ${noteHtml}
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center bg-gray-100 text-gray-500 text-[9px] md:text-[10px] font-bold px-1.5 md:px-2 py-0.5 rounded md:rounded-md max-w-full">
                                                        <i class="fas fa-wallet mr-1 shrink-0"></i>
                                                        <span class="truncate">${tx.wallet_name}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Kanan: Nominal & Tombol Hapus -->
                                        <!-- Ditambahkan shrink-0 agar nominal & tombol hapus tidak ikut menyusut gepeng -->
                                        <div class="flex items-center gap-2 md:gap-4 ml-2 shrink-0">
                                            <p class="font-black ${amountColor} text-xs md:text-base whitespace-nowrap">
                                                ${amountPrefix}${formatRupiah(tx.amount)}
                                            </p>
                                            
                                            <!-- Ukuran Tombol Hapus Diperkecil di HP -->
                                            <button onclick="deleteTransaction(${tx.id})" class="w-7 h-7 md:w-10 md:h-10 rounded-lg md:rounded-xl bg-red-50 text-red-400 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm shrink-0" title="Hapus Transaksi">
                                                <i class="fas fa-trash-alt text-[10px] md:text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                `;
                            });

                            groupHtml += `</div></div>`; // Tutup wadah putih
                            container.innerHTML += groupHtml;
                        }
                    } else {
                        container.innerHTML = `
                            <div class="bg-white rounded-3xl p-10 text-center border border-gray-100">
                                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto text-gray-300 text-3xl mb-4"><i class="fas fa-receipt"></i></div>
                                <h3 class="text-xl font-bold text-gray-800">Belum ada riwayat</h3>
                                <p class="text-gray-400 mt-2">Tidak ada transaksi yang tercatat pada periode ini.</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = '<p class="text-center text-red-500 py-10 font-bold">Gagal terhubung ke server.</p>';
                });
        }

        // FUNGSI HAPUS TRANSAKSI
        function deleteTransaction(id) {
            if (typeof showConfirmModal === 'function') {
                showConfirmModal(
                    'warning', 
                    'Hapus Transaksi?', 
                    'Data yang dihapus tidak dapat dikembalikan. Saldo dompet akan menyesuaikan otomatis.', 
                    'Ya, Hapus', 
                    'Batal', 
                    () => { executeDelete(id); } // Ini adalah confirmCallback
                );
            } else {
                if(confirm("Yakin ingin menghapus transaksi ini?")) executeDelete(id);
            }
        }

        function executeDelete(id) {
            fetch('api/transaction.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    // Jika sukses, munculkan centang hijau lalu ambil data riwayat lagi
                    showModal('success', 'Terhapus', data.message, fetchHistory);
                } else {
                    // Jika gagal (seperti memblokir penghapusan hutang), munculkan silang merah.
                    // Perhatikan: kita TIDAK memberikan callback fetchHistory di sini, 
                    // sehingga modal akan tetap diam di layar sampai user menekan tombol Tutup.
                    showModal('error', 'Gagal Dihapus', data.message);
                }
            })
            .catch(err => {
                showModal('error', 'Kesalahan', 'Gagal terhubung ke server.');
            });
        }

        // Panggil saat halaman pertama kali dimuat
        document.addEventListener('DOMContentLoaded', fetchHistory);
    </script>
</body>
</html>