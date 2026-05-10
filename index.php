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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - SmartFinance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: { background: '#F4F7FE', card: '#FFFFFF' }
                }
            }
        }
    </script>
    <style>
        body { background-color: #F4F7FE; -webkit-tap-highlight-color: transparent; }
        .smooth-shadow { box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); }
        .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 15px 40px -10px rgba(0,0,0,0.12); }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        /* Memberikan ruang di bawah agar konten tidak tertutup Bottom Navbar di HP */
        @media (max-width: 768px) { body { padding-bottom: 90px; } }
    </style>
</head>
<body class="text-gray-800 font-sans antialiased">

    <!-- Navbar Atas (Desktop & Mobile) -->
    <?php include 'navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 md:mt-8">
        
        <div class="mb-6 md:mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">Ringkasan Keuangan</h1>
                <p class="text-sm md:text-base text-gray-500 font-medium mt-1">Pantau arus kas harianmu dengan mudah.</p>
            </div>
            <!-- Tombol Atas Disembunyikan Penuh di HP (hidden md:flex) -->
            <button onclick="openActionMenu()" class="hidden md:flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-colors shadow-lg shadow-blue-500/30">
                <i class="fas fa-plus"></i> Transaksi Baru
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8">
            
            <div class="lg:col-span-5 space-y-6 md:space-y-8">
                
                <!-- KARTU SALDO UTAMA -->
                <div class="bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-400 rounded-[24px] p-6 md:p-8 text-white shadow-xl shadow-blue-500/30 relative overflow-hidden">
                    <div class="absolute -right-6 -top-6 w-32 h-32 bg-white opacity-20 rounded-full blur-2xl"></div>
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-cyan-300 opacity-20 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex justify-between items-center mb-2">
                            <p class="text-blue-100 font-medium text-xs md:text-sm uppercase tracking-wider">Total Saldo Aktif</p>
                            <i class="fas fa-ellipsis-h text-blue-100 cursor-pointer hover:text-white"></i>
                        </div>
                        <!-- truncate agar tidak jebol jika saldo triliunan -->
                        <h2 id="balanceText" class="text-3xl md:text-5xl font-extrabold tracking-tight mb-4 truncate">...</h2>
                        
                        <!-- Rincian Dompet -->
                        <div class="mt-5 md:mt-6 pt-5 md:pt-6 border-t border-white/20">
                            <p class="text-[10px] md:text-xs text-blue-100 font-medium mb-3 uppercase tracking-wider">Rincian Dompet</p>
                            <div id="dashboardWalletList" class="flex gap-3 overflow-x-auto hide-scrollbar pb-2 mb-2">
                                <p class="text-sm text-blue-100"><i class="fas fa-spinner fa-spin"></i> Memuat dompet...</p>
                            </div>
                        </div>
                        
                        <!-- Pemasukan & Pengeluaran (DIPERBAIKI: Anti Jebol) -->
                        <div class="flex items-center justify-between gap-2 md:gap-4 bg-white/10 p-3 md:p-4 rounded-2xl backdrop-blur-sm border border-white/20">
                            <!-- Pemasukan -->
                            <div class="flex items-center gap-2 md:gap-3 flex-1 min-w-0">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-green-400/20 rounded-full flex items-center justify-center text-green-300 shrink-0">
                                    <i class="fas fa-arrow-down text-sm md:text-base"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[9px] md:text-xs text-blue-100 font-medium">Pemasukan</p>
                                    <p id="incomeText" class="font-bold text-white text-sm md:text-base tracking-wide truncate w-full">...</p>
                                </div>
                            </div>
                            <!-- Garis Pemisah -->
                            <div class="w-px h-8 bg-white/20 shrink-0"></div>
                            <!-- Pengeluaran -->
                            <div class="flex items-center gap-2 md:gap-3 flex-1 min-w-0 pl-1 md:pl-2">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-red-400/20 rounded-full flex items-center justify-center text-red-300 shrink-0">
                                    <i class="fas fa-arrow-up text-sm md:text-base"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[9px] md:text-xs text-blue-100 font-medium">Pengeluaran</p>
                                    <p id="expenseText" class="font-bold text-white text-sm md:text-base tracking-wide truncate w-full">...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATISTIK PENGELUARAN -->
                <div class="bg-white rounded-[24px] p-6 smooth-shadow border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-800 text-lg">Statistik</h3>
                        <select id="timeFilter" onchange="loadDashboard()" class="text-xs md:text-sm text-gray-500 bg-gray-50 border border-gray-200 px-2 py-1.5 rounded-lg font-medium outline-none focus:border-blue-500 cursor-pointer">
                            <option value="this_month">Bulan Ini</option>
                            <option value="all">Semua</option>
                        </select>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <div class="relative h-40 w-40 md:h-48 md:w-48 mb-6">
                            <canvas id="expenseChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-gray-400 text-[10px] md:text-xs font-semibold uppercase">Total</span>
                                <span id="expenseTotalLabel" class="text-gray-800 font-extrabold text-base md:text-lg">...</span>
                            </div>
                        </div>
                        
                        <div id="chartLegend" class="w-full space-y-2 md:space-y-3">
                            <p class="text-center text-sm text-gray-400">Memuat grafik...</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-7 flex flex-col gap-6">
                
                <!-- RIWAYAT TERAKHIR -->
                <div class="bg-white rounded-[24px] p-6 smooth-shadow border border-gray-100 flex-1">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-800 text-lg">Riwayat Terakhir</h3>
                        <a href="riwayat" class="text-xs md:text-sm text-blue-600 font-semibold bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors">Lihat Semua</a>
                    </div>

                    <div id="transactionList" class="space-y-2 md:space-y-4">
                        <div class="flex justify-center items-center py-10">
                            <i class="fas fa-spinner fa-spin text-blue-500 text-2xl mr-3"></i> 
                            <span class="text-gray-500 font-medium">Memuat transaksi...</span>
                        </div>
                    </div>
                </div>

                <!-- TIPS KEUANGAN -->
                <div class="bg-gradient-to-r from-[#F0F9FF] to-[#E0F2FE] border border-blue-100 rounded-[20px] p-4 md:p-5 flex items-center gap-4 hover-lift">
                    <div class="bg-white w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center text-blue-500 shadow-sm shrink-0">
                        <i class="fas fa-lightbulb text-lg md:text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs md:text-sm font-bold text-blue-900 mb-0.5">Tips Keuangan</p>
                        <p class="text-[10px] md:text-xs text-blue-700 font-medium">Jangan <span class="font-bold">Berhemat</span>. Nikmati hidupmu, jangan menyiksa diri</p>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- ========================================== -->
    <!-- FLOATING ACTION BUTTON (KHUSUS PC/LAPTOP)  -->
    <!-- ========================================== -->
    <button onclick="openActionMenu()" class="hidden md:flex fixed bottom-8 right-8 z-[90] items-center gap-3 bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-full font-extrabold transition-all hover:scale-105 shadow-[0_10px_25px_rgba(37,99,235,0.4)]">
        <i class="fas fa-plus text-xl"></i> Tambah Transaksi
    </button>

    <?php include 'navbar_hp.php'; ?>

    <script>
        const formatRupiah = (angka) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
        const formatShortNumber = (num) => {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + ' Jt';
            if (num >= 1000) return (num / 1000).toFixed(1) + ' Rb';
            return num.toString();
        };

        const chartColors = ['#0061FF', '#60EFFF', '#E2E8F0', '#93C5FD', '#3B82F6'];
        let myChart = null;

        function loadDashboard() {
            const filterValue = document.getElementById('timeFilter').value;
            fetch(`api/dashboard.php?user_id=1&filter=${filterValue}`)
                .then(res => res.json())
                .then(response => {
                    if(response.status === 'success') {
                        const data = response.data;

                        document.getElementById('balanceText').innerText = formatRupiah(data.summary.balance);
                        document.getElementById('incomeText').innerText = formatRupiah(data.summary.income);
                        document.getElementById('expenseText').innerText = formatRupiah(data.summary.expense);
                        document.getElementById('expenseTotalLabel').innerText = formatShortNumber(data.summary.expense);
                        
                        // Render Chart
                        const ctx = document.getElementById('expenseChart').getContext('2d');
                        if(myChart) myChart.destroy(); 
                        myChart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: data.chart.labels,
                                datasets: [{
                                    data: data.chart.data,
                                    backgroundColor: chartColors,
                                    borderWidth: 0, hoverOffset: 5, borderRadius: 4
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false, cutout: '80%',
                                plugins: { legend: { display: false } }
                            }
                        });

                        // Render Legend
                        const legendContainer = document.getElementById('chartLegend');
                        legendContainer.innerHTML = '';
                        const totalChartValue = data.chart.data.reduce((a, b) => a + b, 0);

                        if(data.chart.labels.length > 0) {
                            data.chart.labels.forEach((label, index) => {
                                const percentage = totalChartValue > 0 ? Math.round((data.chart.data[index] / totalChartValue) * 100) : 0;
                                legendContainer.innerHTML += `
                                    <div class="flex justify-between items-center text-xs md:text-sm">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: ${chartColors[index % chartColors.length]}"></span>
                                            <span class="font-medium text-gray-600 truncate">${label}</span>
                                        </div>
                                        <span class="font-bold text-gray-800 shrink-0 ml-2">${percentage}%</span>
                                    </div>
                                `;
                            });
                        }

                        // Render Riwayat dengan Perbaikan Ikon Gambar & Overflow
                        const listContainer = document.getElementById('transactionList');
                        listContainer.innerHTML = '';

                        if(data.recent_transactions.length > 0) {
                            data.recent_transactions.forEach(trx => {
                                const isIncome = trx.type === 'income';
                                const sign = isIncome ? '+ ' : '- ';
                                const amountColor = isIncome ? 'text-green-500' : 'text-gray-800';
                                
                                const dateObj = new Date(trx.date);
                                const dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

                                // DETEKSI IKON GAMBAR (Lokal vs FontAwesome)
                                const safeIconTrx = trx.icon_name ? trx.icon_name : 'wallet';
                                const isImageTrx = safeIconTrx.includes('.');
                                const iconTrxHtml = isImageTrx 
                                    ? `<img src="${safeIconTrx}" class="w-6 h-6 object-contain" alt="Icon" onerror="this.style.display='none'">`
                                    : `<i class="fas fa-${safeIconTrx}"></i>`;

                                listContainer.innerHTML += `
                                    <div class="group flex justify-between items-center p-2 md:p-3 hover:bg-gray-50 rounded-2xl transition-colors cursor-pointer border border-transparent hover:border-gray-100">
                                        
                                        <!-- Kiri (Ikon & Nama) pakai flex-1 min-w-0 agar bisa dipotong (...) -->
                                        <div class="flex items-center gap-3 md:gap-4 flex-1 min-w-0">
                                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl flex items-center justify-center text-lg md:text-xl shrink-0 group-hover:scale-110 transition-transform" 
                                                 style="background-color: ${trx.color}15; color: ${trx.color};">
                                                ${iconTrxHtml}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-gray-800 text-sm md:text-base mb-0.5 truncate">${trx.note || trx.cat_name}</p>
                                                <p class="text-[10px] md:text-xs font-medium text-gray-400 truncate">${trx.cat_name} • ${dateStr}</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Kanan (Nominal) pakai shrink-0 agar tidak gepeng -->
                                        <p class="font-bold ${amountColor} text-sm md:text-base shrink-0 ml-2">
                                            ${sign}${formatRupiah(trx.amount)}
                                        </p>
                                    </div>
                                `;
                            });
                        } else {
                            listContainer.innerHTML = '<p class="text-center text-gray-400 py-10 font-medium">Belum ada transaksi.</p>';
                        }
                    }
                });
        }
        
        // Render Dompet (Disesuaikan dengan Ikon Gambar)
        fetch('api/wallet.php')
            .then(res => res.json())
            .then(wData => {
                if(wData.status === 'success') {
                    const wList = document.getElementById('dashboardWalletList');
                    wList.innerHTML = '';
                    wData.data.forEach(w => {
                        const safeIconW = w.icon_name ? w.icon_name : 'wallet';
                        const isImageW = safeIconW.includes('.');
                        const iconHtmlW = isImageW 
                            ? `<img src="${safeIconW}" class="w-4 h-4 object-contain" alt="Icon" onerror="this.style.display='none'">`
                            : `<i class="fas fa-${safeIconW} text-xs"></i>`;

                        wList.innerHTML += `
                            <div class="bg-white/10 border border-white/20 backdrop-blur-sm rounded-xl p-2.5 md:p-3 shrink-0 flex items-center gap-2 md:gap-3 w-36 md:w-40 hover:bg-white/20 transition-colors cursor-pointer" onclick="window.location.href='manajemen'">
                                <div class="w-7 h-7 md:w-8 md:h-8 rounded-full flex items-center justify-center text-white shrink-0" style="background-color: ${w.color}">
                                    ${iconHtmlW}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[9px] md:text-[10px] text-blue-100 font-bold uppercase truncate w-full">${w.name}</p>
                                    <p class="text-xs md:text-sm font-extrabold text-white truncate">${formatRupiah(w.balance)}</p>
                                </div>
                            </div>
                        `;
                    });
                }
            });

        document.addEventListener('DOMContentLoaded', loadDashboard);
    </script>
</body>
</html>