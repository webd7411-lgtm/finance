const PDFDocument = require('pdfkit');
const fs = require('fs');
const path = require('path');

const doc = new PDFDocument({
    size: 'A4',
    margin: 40,
    bufferPages: true
});

const outputPathRoot = path.join(__dirname, 'FinanceDesk_User_Manual.pdf');
const outputPathPublic = path.join(__dirname, 'public', 'FinanceDesk_User_Manual.pdf');

const writeStreamRoot = fs.createWriteStream(outputPathRoot);
doc.pipe(writeStreamRoot);

// Palette
const primaryColor = '#1d4ed8';   // Blue
const secondaryColor = '#0f172a'; // Dark Slate
const accentColor = '#059669';    // Green
const warningColor = '#d97706';   // Amber
const textDark = '#1e293b';       // Charcoal
const textMuted = '#475569';      // Gray
const lightBg = '#f8fafc';        // Off-white
const borderColor = '#e2e8f0';    // Light border

// Helper to draw headers
function drawSectionHeader(title, iconSymbol) {
    if (doc.y > 680) {
        doc.addPage();
    }
    doc.moveDown(0.8);
    const y = doc.y;
    doc.rect(40, y, 515, 26).fillAndStroke(lightBg, borderColor);
    doc.fillColor(primaryColor).fontSize(12).font('Helvetica-Bold')
       .text(`${iconSymbol}  ${title}`, 48, y + 6);
    doc.y = y + 32;
    doc.font('Helvetica').fontSize(9).fillColor(textDark);
}

function drawSubHeader(title) {
    if (doc.y > 700) doc.addPage();
    doc.moveDown(0.4);
    doc.fillColor(secondaryColor).fontSize(10.5).font('Helvetica-Bold')
       .text(title);
    doc.moveDown(0.2);
    doc.font('Helvetica').fontSize(9).fillColor(textDark);
}

function drawBullet(title, desc) {
    if (doc.y > 720) doc.addPage();
    const x = 52;
    const y = doc.y;
    doc.circle(46, y + 4.5, 2.5).fill(primaryColor);
    doc.fillColor(secondaryColor).font('Helvetica-Bold').fontSize(9)
       .text(title + ': ', x, y, { continued: true });
    doc.fillColor(textDark).font('Helvetica').fontSize(9)
       .text(desc, { indent: 0 });
    doc.moveDown(0.3);
}

function drawInfoBox(text, type = 'info') {
    if (doc.y > 690) doc.addPage();
    const y = doc.y;
    const bg = type === 'warning' ? '#fffbeb' : '#eff6ff';
    const border = type === 'warning' ? '#f59e0b' : '#3b82f6';
    const icon = type === 'warning' ? '[!]' : '[i]';
    
    doc.rect(40, y, 515, 28).fillAndStroke(bg, border);
    doc.fillColor(border).font('Helvetica-Bold').fontSize(8.5)
       .text(`${icon} NOTE: `, 48, y + 8, { continued: true });
    doc.fillColor(textDark).font('Helvetica').fontSize(8.5)
       .text(text);
    doc.y = y + 34;
}

// ==========================================
// 1. COVER / HEADER BANNER
// ==========================================
doc.rect(0, 0, 595.28, 120).fill('#090e17');

// Logo / Title in Banner
doc.fillColor('#3b82f6').fontSize(26).font('Helvetica-Bold')
   .text('FinanceDesk', 40, 30, { continued: true });
doc.fillColor('#ffffff').fontSize(26).font('Helvetica-Bold')
   .text(' - Mukammal User Manual');

doc.fillColor('#94a3b8').fontSize(11).font('Helvetica')
   .text('Shift Closing, Day Closing, Party Ledgers & Multi-Account Management System', 40, 62);

doc.fillColor('#38bdf8').fontSize(8.5).font('Helvetica-Bold')
   .text('OFFICIAL GUIDE & WORKFLOW MANUAL  |  URDU & ENGLISH GUIDE  |  VERSION 1.0', 40, 84);

doc.y = 135;

// Intro text
doc.fillColor(textDark).fontSize(9.5).font('Helvetica')
   .text('Yeh software retail shops, agencies, wholesale stores aur daily cash-counter karobaron ke liye design kiya gaya hai. Iska maqsad subah aur sham ki cash counter sales, bank transfers, party accounts (Khata), aur daily cash box ko 100% transparent aur audit-proof banana hai.', {
       align: 'justify',
       lineGap: 2
   });

drawInfoBox('Pura software Role-Based Access Control (Owner, Incharge, Cashier) par mushtamil hai aur har action ka Audit Log record hota hai.', 'info');

// ==========================================
// 2. USER ROLES & ACCESS CONTROL
// ==========================================
drawSectionHeader('1. Users, Roles & Permissions (Ikhtiyarat)', '[1]');

drawBullet('Owner (Malik / Administrator)', 'Pura system access. Naye users banana, edit karna, shifts ko unlock karna, band din ko reopen karna, aur mukammal Audit Trail dekhna.');
drawBullet('Incharge (Manager / Supervisor)', 'Parties, Accounts aur Expense Categories setup karna. Cashiers ki shift closing check kar ke verify/lock karna aur Day Closing run karna.');
drawBullet('Cashier (Counter Operator)', 'Sirf apni Morning ya Evening shift closing submit karna, counter sale note count karna, aur daily vouchers (Payment In/Out) enter karna.');

drawSubHeader('Users Management me kaam karne ka tareeqa:');
drawBullet('Add User', 'Users & Roles page par jayein -> "Add New User" button dabayein -> Modal me Name, Email, Password, Role aur Status select kar ke Save karein.');
drawBullet('Edit User', 'Table me Pencil (Edit) icon dabayein -> Modal open hoga -> Details update karein aur Save Changes karein.');
drawBullet('Toggle Status', 'Power icon par click kar ke user ko foran Active ya Inactive karein.');

// ==========================================
// 3. MASTER SETUP (PARTIES, ACCOUNTS, CATEGORIES)
// ==========================================
drawSectionHeader('2. Master Setup (Parties, Accounts & Categories)', '[2]');

drawSubHeader('A. Parties Directory (Khata & Ledgers)');
doc.text('Parties directory me un tamam logon ka record hota hai jin se karobar ka len den hai:', { lineGap: 2 });
doc.moveDown(0.2);
drawBullet('Supplier', 'Woh companies ya distributors jin se maal khareeda jata hai aur unhe payments (Adaigi) karni hoti hai.');
drawBullet('Trader', 'Woh karobari dost jin se len-den dono taraf hota hai.');
drawBullet('Staff', 'Dukan ke mulazmeen jin ko peshgi (Advance), kharcha ya tankhwah ka hisab rakhna ho.');
drawBullet('Customer', 'Udhar maal lene wale grahak jin se paise wasool (Receivable) karne hain.');
drawBullet('Party Kaise Banayein?', 'Parties page par "Add New Party" modal kholein -> Name, Type, Phone, Address aur Opening Balance enter karein.');

drawSubHeader('B. Payment Accounts Setup');
drawBullet('Cash In Hand', 'Counter ka main cash drawer ya tijori.');
drawBullet('Bank Accounts', 'HBL, Meezan, Bank Alfalah waghera (Account No & Branch code ke sath).');
drawBullet('Digital Wallets', 'EasyPaisa, JazzCash, Raast, POS Card Machine.');
doc.text('Faida: Jab bhi koi transaction hoti hai, mutaliqa account ka live balance foran update ho jata hai.', { lineGap: 2 });

drawSubHeader('C. Expense Categories');
drawBullet('Kharchon Ki Aqsaam', 'Chai / Pani, Bijli Bill, Dukan Kiraya, Safai, Stationery, Transport. Nayi category modal ke zariye 2 second me add hoti hai.');

// ==========================================
// 4. SHIFT CLOSING WORKFLOW
// ==========================================
drawSectionHeader('3. Shift Closing Module (Counter Shift Band Karna)', '[3]');

doc.text('Har shift (Subah / Sham) ke ikhtitam par Cashier ko counter band karne ke liye yeh steps lene hain:', { lineGap: 2 });
doc.moveDown(0.2);

drawBullet('Step 1 - Shift Select Karein', 'Date aur Shift Type muntakhab karein (Morning Shift ya Evening Shift).');
drawBullet('Step 2 - Sale Figures', 'POS software se nikalne wali Total Sale amount aur Invoices count enter karein.');
drawBullet('Step 3 - Returns & Counter Expenses', 'Agar counter se kisi grahak ko return cash diya ya chota kharcha (Chai/Pani) nikala gaya ho, toh woh amount likhein.');
drawBullet('Step 4 - Physical Cash Denominations (Note Counter)', 'Drawer me mojood tamam notes ginein aur software me daalein:');
doc.text('   * Rs. 5000 | Rs. 1000 | Rs. 500 | Rs. 100 | Rs. 50 | Rs. 20 | Rs. 10 | Coins', { indent: 20 });
doc.text('   -> Software khud ba khud physical cash ka grand total calculate karega.', { indent: 20, lineGap: 2 });
drawBullet('Step 5 - Digital / Bank Payments', 'Jo raqam customers ne Raast, QR Code ya Bank Transfer ke zariye ada ki, us Bank Account aur amount ko select karein.');
drawBullet('Step 6 - Difference Calculation (Farq)', 'Software foran bata dega:');
doc.text('   * Expected Cash Sale vs Actual Received Cash.', { indent: 20 });
doc.text('   * Difference: Zero (Exact Match) | Positive (Ziada Cash) | Negative (Shortage / Cash Kammi).', { indent: 20, lineGap: 2 });
drawBullet('Step 7 - Shift Lock', 'Incharge ya Owner shift verify kar ke "Lock" kar deta hai takay koi data tabdeel na kiya ja sakay.');

// ==========================================
// 5. DAILY VOUCHERS (TRANSACTIONS)
// ==========================================
drawSectionHeader('4. Daily Vouchers (Payments In & Payments Out)', '[4]');

drawSubHeader('A. Payment In (Raqam Wasool Hona):');
drawBullet('Kab use karein?', 'Jab kisi Customer ya Trader se udhar ki wasooli aaye, ya bank se drawer me cash nikala jaye.');
drawBullet('Tareeqa', 'Payments In/Out page -> Party select karein -> Receiving Account (Cash ya Bank) select karein -> Amount daal kar save karein. Khata aur account automatically update ho jayenge.');

drawSubHeader('B. Payment Out (Raqam Ada Karna):');
drawBullet('Kab use karein?', 'Jab kisi Supplier ko check/cash ada kiya jaye, Staff ko advance diya jaye, ya dukan ka bara kharcha kiya jaye.');
drawBullet('Tareeqa', 'Party select karein (ya Direct Expense Category) -> Payment Source Account select karein -> Amount enter karein aur save karein.');

drawSubHeader('C. Transfer Funds (Account to Account Transfer):');
drawBullet('Kab use karein?', 'Jab Bank se cash nikal kar Drawer me shamil karna ho, ya Counter Cash bank/wallet me jama karwana ho.');
drawBullet('Tareeqa', '"Transfer Funds" button dabayein -> Transfer From (Source Account) aur Transfer To (Destination Account) select karein -> Amount enter kar ke 1-click me transfer karein. Dono accounts foran update ho jate hain.');

// ==========================================
// 6. DAY CLOSING MODULE (ROZANA KI MUKAMMAL CLOSING)
// ==========================================
drawSectionHeader('5. Day Closing Module (Rozana Ki Complete Closing)', '[5]');

doc.text('Din ke aakhir par (Raat ko) jab dono shifts mukammal ho jayein, Incharge ya Owner "Day Closing" page par jata hai:', { lineGap: 2 });
doc.moveDown(0.2);

drawBullet('1. Automated Opening Cash', 'Pichlay din ka Closing Cash aaj ke din ka Opening Cash ban kar auto load hota hai.');
drawBullet('2. Shift Merging', 'Morning Shift Cash + Evening Shift Cash khud ba khud merge ho jate hain.');
drawBullet('3. Direct Vouchers Addition', 'Din bhar ki Payments In add hoti hain aur Payments Out / Expenses minus hote hain.');
drawBullet('4. Final Cash in Drawer', 'System Drawer me mojood hona chahiye aakhri cash calculate karta hai.');
drawBullet('5. Shortage / Excess Audit', 'Dono shifts ka kul farq (Difference) clear highlight hota hai.');
drawBullet('6. Finalize & Close Day', '"Close Day" button dabane se din lock ho jata hai aur aglay din ke liye opening cash register ho jata hai.');
drawBullet('7. Emergency Reopen', 'Agar koi bhool chook ho jaye toh sirf Owner din ko "Reopen" kar sakta hai.');

// ==========================================
// 7. LEDGERS & REPORTS (KHATAY AUR REPORTING)
// ==========================================
drawSectionHeader('6. Ledgers & Reports (Khatay aur Tafseeli Reports)', '[6]');

drawBullet('Party Ledger (Khata Statement)', 'Kisi bhi party ka naam aur date range select karein -> Opening Balance, har transaction (Debit/Credit) aur Running Balance ke sath printable statement mil jati hai. Customer ko print ya PDF send karne ke liye tayyar.');
drawBullet('Cash Book Register', 'Rozana ka entry-wise cash flow: Subah kitna cash tha, shifts se kitna aya, expenses kitnay huay aur sham ko kitna cash bacha.');
drawBullet('Bank Book Register', 'Har bank aur digital wallet ka alag register. Kis tareekh ko kitne transfers aye aur kahan transfer huay.');
drawBullet('Daily Closing Summary Report', 'Boss / Owner ke liye 1-page executive summary jo print nikal kar dukan ke record me file ki ja sakti hai.');
drawBullet('Discrepancy & Variance Audit', 'Cashiers ki shortages aur excess ka tareekh-waar audit takay cash loss foran trace ho sakay.');

// ==========================================
// 8. AUDIT TRAIL & SYSTEM SECURITY
// ==========================================
drawSectionHeader('7. Security, Audit Logs & Best Practices', '[7]');

drawBullet('Audit Trail', 'System har ahem amal ko record karta hai: Kis user ne login kiya, kis ne shift lock/unlock ki, kis ne voucher delete kiya, aur kis IP address se kiya gaya.');
drawBullet('Best Practices for Daily Operations:', 'Neeche diye gaye usoolon par pabandi se amal karein:');
doc.text('   1. Har shift ka cashier shift khatam hote hi foran notes count kar ke enter kare.', { indent: 20 });
doc.text('   2. Incharge shift check kar ke usi waqt "Lock" kare takay koi badalti na ho sake.', { indent: 20 });
doc.text('   3. Kisi bhi party ko adaigi ya wasooli direct voucher ke zariye record karein takay khata out na ho.', { indent: 20 });
doc.text('   4. Raat ko "Day Closing" zaroor mukammal karein takay aglay din ka opening balance theek transfer ho.', { indent: 20, lineGap: 4 });

// Page Numbers & Footers
const range = doc.bufferedPageRange();
for (let i = range.start; i < (range.start + range.count); i++) {
    doc.switchToPage(i);
    // Footer line
    doc.rect(40, 792, 515, 0.5).fill('#cbd5e1');
    doc.fillColor('#64748b').fontSize(8).font('Helvetica')
       .text('FinanceDesk System Manual  |  Prowave Technologies', 40, 800, { lineBreak: false });
    doc.text(`Page ${i + 1} of ${range.count}`, 490, 800, { align: 'right', lineBreak: false });
}

doc.end();

writeStreamRoot.on('finish', () => {
    // Also copy to public directory
    fs.copyFileSync(outputPathRoot, outputPathPublic);
    console.log('PDF generated successfully at:');
    console.log(outputPathRoot);
    console.log(outputPathPublic);
});
