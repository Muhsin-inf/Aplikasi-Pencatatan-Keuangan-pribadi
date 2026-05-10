
const logoDictionary = {
    // === E-WALLET INDONESIA ===
    'shopee': 'shopee.co.id',
    'shopeepay': 'shopee.co.id',
    'spay': 'shopee.co.id',

    'gopay': 'gojek.com',
    'gojek': 'gojek.com',

    'ovo': 'ovo.id',

    'dana': 'dana.id',

    'linkaja': 'linkaja.id',
    'link aja': 'linkaja.id',

    'sakuku': 'bca.co.id',

    'isaku': 'isaku.id',

    'doku': 'doku.com',

    'flip': 'flip.id',

    'payfazz': 'payfazz.com',

    'fazz': 'fazz.com',

    'blu': 'blu.id',

    // === QR & PAYMENT ===
    'qris': 'bankindonesia.go.id',

    'midtrans': 'midtrans.com',

    'xendit': 'xendit.co',

    'duitku': 'duitku.com',

    // === BANK INDONESIA ===
    'bca': 'bca.co.id',
    'klikbca': 'bca.co.id',

    'bri': 'bri.co.id',
    'brimo': 'bri.co.id',

    'bni': 'bni.co.id',

    'mandiri': 'bankmandiri.co.id',
    'livin': 'bankmandiri.co.id',

    'bsi': 'bankbsi.co.id',

    'btn': 'btn.co.id',

    'cimb': 'cimbniaga.co.id',
    'cimb niaga': 'cimbniaga.co.id',

    'permata': 'permatabank.com',

    'danamon': 'danamon.co.id',

    'maybank': 'maybank.co.id',

    'ocbc': 'ocbc.id',
    'ocbc nisp': 'ocbc.id',

    // === BANK DIGITAL / FINTECH ===
    'jago': 'jago.com',

    'jenius': 'jenius.com',

    'seabank': 'seabank.co.id',

    'neobank': 'neobank.co.id',
    'neo': 'neobank.co.id',

    'allobank': 'allobank.com',

    'line bank': 'linebank.co.id',

    'superbank': 'superbank.id',

    // === INTERNASIONAL (opsional, kalau mau support) ===
    'paypal': 'paypal.com',

    'wise': 'wise.com',

    'payoneer': 'payoneer.com',

    'stripe': 'stripe.com'
};

// Fungsi untuk mendeteksi ketikan pengguna
function detectWalletLogo(inputElement) {
    const keyword = inputElement.value.toLowerCase();
    const logoPreview = document.getElementById('logoPreview');
    const iconInput = document.getElementById('walletIconInput'); // Input hidden untuk dikirim ke database
    
    let foundDomain = null;

    // Cari apakah kata yang diketik ada di kamus kita
    for (const [key, domain] of Object.entries(logoDictionary)) {
        if (keyword.includes(key)) {
            foundDomain = domain;
            break;
        }
    }

    if (foundDomain) {
        // Jika ketemu, ubah preview jadi logo asli dari Clearbit
        const logoUrl = `https://logo.clearbit.com/${foundDomain}`;
        logoPreview.innerHTML = `<img src="${logoUrl}" class="w-8 h-8 object-contain rounded-md" alt="Logo">`;
        
        // Simpan URL gambar ini ke input form agar masuk ke database (kolom icon_name)
        iconInput.value = logoUrl;
    } else {
        // Jika tidak ketemu (misal: Uang Tunai), kembalikan ke ikon dompet biasa
        logoPreview.innerHTML = `<i class="fas fa-wallet text-gray-400 text-xl"></i>`;
        iconInput.value = 'wallet'; 
    }
}