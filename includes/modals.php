<!-- Advanced Search Modal -->
<div id="advancedSearchModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.8); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(5px); padding:20px; overflow-y:auto;">
  <div style="background:#fff; border-radius:var(--radius-lg); width:100%; max-width:800px; padding:30px; position:relative; box-shadow:0 20px 40px rgba(0,0,0,0.2); margin: auto;">
    <button type="button" onclick="document.getElementById('advancedSearchModal').style.display='none'" style="position:absolute; top:20px; left:20px; background:#f1f5f9; border:none; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-dark); transition: background 0.3s;"><i class="ph ph-x"></i></button>
    
    <h3 style="margin:0 0 25px; font-size:22px; font-weight:800; color:var(--text-dark); display:flex; align-items:center; gap:8px;"><i class="ph-fill ph-faders" style="color:var(--primary);"></i> البحث المتقدم الشامل</h3>
    
    <form action="/auctions.php" method="GET">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:20px;">
        <!-- Make -->
        <div>
          <label style="display:block; margin-bottom:8px; font-size:14px; font-weight:700; color:var(--text-dark);">ماركة المركبة</label>
          <div style="border:1px solid var(--border-light); border-radius:var(--radius-md); padding:0 15px; display:flex; align-items:center; gap:10px;">
            <i class="ph ph-car" style="color:var(--text-muted); font-size:20px;"></i>
            <select name="make" id="modalCarMake" style="width:100%; padding:12px 0; border:none; outline:none; font-family:inherit; font-size:15px; background:transparent;" onchange="updateModalModels()">
<option value="">الجميع</option>
              <option value="تويوتا">تويوتا</option>
              <option value="هيونداي">هيونداي</option>
              <option value="نيسان">نيسان</option>
              <option value="فورد">فورد</option>
              <option value="كيا">كيا</option>
              <option value="شيفروليه">شيفروليه</option>
              <option value="جي إم سي">جي إم سي</option>
              <option value="لكزس">لكزس</option>
              <option value="مرسيدس">مرسيدس</option>
              <option value="بي إم دبليو">بي إم دبليو</option>
              <option value="أودي">أودي</option>
              <option value="بورش">بورش</option>
              <option value="لاند روفر">لاند روفر</option>
              <option value="مازدا">مازدا</option>
              <option value="هوندا">هوندا</option>
              <option value="ايسوزو">ايسوزو</option>
              <option value="ميتسوبيشي">ميتسوبيشي</option>
              <option value="شانجان">شانجان</option>
              <option value="جيلي">جيلي</option>
              <option value="إم جي (MG)">إم جي (MG)</option>
              <option value="هافال">هافال</option>
              <option value="دودج">دودج</option>
              <option value="جيب">جيب</option>
              <option value="كرايسلر">كرايسلر</option>
              <option value="كاديلاك">كاديلاك</option>
              <option value="لينكولن">لينكولن</option>
              <option value="رينو">رينو</option>
              <option value="بيجو">بيجو</option>
              <option value="فولكس واجن">فولكس واجن</option>
              <option value="سوزوكي">سوزوكي</option>
              <option value="أستون مارتن">أستون مارتن</option>
              <option value="فيراري">فيراري</option>
              <option value="لامبورجيني">لامبورجيني</option>
              <option value="ماكلارين">ماكلارين</option>
              <option value="بنتلي">بنتلي</option>
              <option value="رولز رويس">رولز رويس</option>
            </select>
          </div>
        </div>
        
        <!-- Model -->
        <div>
          <label style="display:block; margin-bottom:8px; font-size:14px; font-weight:700; color:var(--text-dark);">الموديل</label>
          <div style="border:1px solid var(--border-light); border-radius:var(--radius-md); padding:0 15px; display:flex; align-items:center; gap:10px;">
            <i class="ph ph-steering-wheel" style="color:var(--text-muted); font-size:20px;"></i>
            <select name="model" id="modalCarModel" style="width:100%; padding:12px 0; border:none; outline:none; font-family:inherit; font-size:15px; background:transparent;" disabled>
              <option value="">جميع الموديلات</option>
            </select>
          </div>
        </div>

        <!-- City -->
        <div>
          <label style="display:block; margin-bottom:8px; font-size:14px; font-weight:700; color:var(--text-dark);">المدينة</label>
          <div style="border:1px solid var(--border-light); border-radius:var(--radius-md); padding:0 15px; display:flex; align-items:center; gap:10px;">
            <i class="ph ph-map-pin" style="color:var(--text-muted); font-size:20px;"></i>
            <select name="city" style="width:100%; padding:12px 0; border:none; outline:none; font-family:inherit; font-size:15px; background:transparent;">
              <option value="">جميع مدن المملكة</option>
              <option value="riyadh">الرياض</option>
              <option value="jeddah">جدة</option>
              <option value="dammam">الدمام</option>
              <option value="mecca">مكة المكرمة</option>
              <option value="medina">المدينة المنورة</option>
            </select>
          </div>
        </div>
        
        <!-- Seller -->
        <div>
          <label style="display:block; margin-bottom:8px; font-size:14px; font-weight:700; color:var(--text-dark);">الشركة المنظمة / المعرض</label>
          <div style="border:1px solid var(--border-light); border-radius:var(--radius-md); padding:0 15px; display:flex; align-items:center; gap:10px;">
            <i class="ph ph-buildings" style="color:var(--text-muted); font-size:20px;"></i>
            <select name="seller" style="width:100%; padding:12px 0; border:none; outline:none; font-family:inherit; font-size:15px; background:transparent;">
              <option value="">جميع الشركات</option>
              <option value="wefaq">شركة الوفاق</option>
              <option value="budget">مزادات بدجت</option>
              <option value="national">الشركة الوطنية</option>
            </select>
          </div>
        </div>
      </div>
      
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:30px; margin-bottom:30px; background:#f8fafc; padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-light);">
        <!-- Year Range -->
        <div>
          <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <label style="font-size:14px; font-weight:700; color:var(--text-dark);">سنة الصنع</label>
            <span id="modalYearVal" class="font-en" style="color:#1bc976; font-weight:800;">2010 - 2024</span>
          </div>
          <div style="position:relative; height:6px; background:#334155; border-radius:3px;">
             <div style="position:absolute; left:0; right:20%; height:100%; background:#1bc976; border-radius:3px;"></div>
             <div style="position:absolute; right:20%; top:-6px; width:18px; height:18px; background:#1bc976; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);"></div>
             <div style="position:absolute; left:0; top:-6px; width:18px; height:18px; background:#1bc976; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);"></div>
             <input type="range" name="year_from" min="2000" max="2024" value="2010" style="position:absolute; width:100%; top:-6px; opacity:0; cursor:pointer;" oninput="document.getElementById('modalYearVal').innerText = this.value + ' - 2024'">
          </div>
        </div>
        
        <!-- Price Range -->
        <div>
          <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <label style="font-size:14px; font-weight:700; color:var(--text-dark);">السعر حتى (ر.س)</label>
            <span id="modalPriceVal" class="font-en" style="color:#1bc976; font-weight:800;">150,000</span>
          </div>
          <div style="position:relative; height:6px; background:#334155; border-radius:3px;">
             <div style="position:absolute; left:0; right:30%; height:100%; background:#1bc976; border-radius:3px;"></div>
             <div style="position:absolute; right:30%; top:-6px; width:18px; height:18px; background:#1bc976; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);"></div>
             <input type="range" name="price_to" min="10000" max="500000" step="5000" value="150000" style="position:absolute; width:100%; top:-6px; opacity:0; cursor:pointer;" oninput="document.getElementById('modalPriceVal').innerText = Number(this.value).toLocaleString()">
          </div>
        </div>
      </div>
      
      <button type="submit" class="btn" style="width:100%; padding:15px; font-size:16px; font-weight:800; display:flex; justify-content:center; align-items:center; gap:8px; border-radius:var(--radius-md); background:#14b8a6; color:#fff; border:none;" font-size:16px; font-weight:800; display:flex; justify-content:center; align-items:center; gap:8px; border-radius:var(--radius-md);">
        <i class="ph ph-magnifying-glass"></i> إظهار النتائج
      </button>
    </form>
    
    <script>
      function updateModalModels() {
          const make = document.getElementById('modalCarMake').value;
          const modelSelect = document.getElementById('modalCarModel');
          const models = {
              'تويوتا': ['كامري', 'كورولا', 'لاند كروزر', 'يارس', 'هايلوكس', 'راف فور', 'اف جي كروزر', 'برادو', 'أفالون', 'شاص', 'ربع', 'سيكويا', 'إنوفا', 'فورتشنر', 'هايلاندر', 'كراون'],
              'هيونداي': ['سوناتا', 'إلنترا', 'توسان', 'سانتافي', 'أزيرا', 'أكسنت', 'كونا', 'باليسيد', 'كريتا', 'فيلوستر', 'إتش1', 'ستاريا', 'جينيسيس'],
              'نيسان': ['باترول', 'صني', 'التيما', 'ماكسيما', 'سنترا', 'إكس تريل', 'كيكس', 'نافارا', 'باثفايندر', 'سفاري', 'ددسن'],
              'فورد': ['تورس', 'اكسبلورر', 'اف-150', 'موستنج', 'إكسبدشن', 'إيدج', 'رينجر', 'فليكس', 'تيريتوري', 'برونكو'],
              'كيا': ['أوبتيما', 'سبورتاج', 'سيراتو', 'سورينتو', 'كادينزا', 'ريو', 'تيلورايد', 'بيجاس', 'سيلتوس', 'سونيت', 'كرنفال', 'K5', 'K8'],
              'شيفروليه': ['تاهو', 'كابريس', 'لومينا', 'ماليبو', 'إمبالا', 'ترافيرس', 'كابتيفا', 'سيلفرادو', 'كامارو', 'كورفيت', 'سوبربان', 'بليزر'],
              'جي إم سي': ['يوكون', 'سييرا', 'أكاديا', 'تيرين', 'سافانا', 'إنفوي'],
              'لكزس': ['LS', 'ES', 'IS', 'GS', 'LX', 'RX', 'NX', 'GX', 'LC', 'RC'],
              'مرسيدس': ['S-Class', 'E-Class', 'C-Class', 'G-Class', 'GLE', 'GLC', 'GLA', 'A-Class', 'Maybach'],
              'بي إم دبليو': ['7 Series', '5 Series', '3 Series', 'X7', 'X6', 'X5', 'X4', 'X3', 'X1', 'M Power'],
              'أودي': ['A8', 'A6', 'A4', 'Q8', 'Q7', 'Q5', 'Q3', 'R8', 'RS'],
              'بورش': ['باناميرا', 'كايين', 'ماكان', '911', 'تايكان', 'كايمان', 'بوكستر'],
              'لاند روفر': ['رينج روفر', 'ديفندر', 'ديسكفري', 'فيلار', 'إيفوك'],
              'مازدا': ['مازدا 6', 'مازدا 3', 'CX-9', 'CX-5', 'CX-3', 'CX-30'],
              'هوندا': ['أكورد', 'سيفيك', 'سيتي', 'بايلوت', 'CR-V', 'HR-V', 'أوديسي'],
              'ايسوزو': ['دي ماكس', 'ام يو اكس'],
              'ميتسوبيشي': ['باجيرو', 'لانسير', 'إكليبس', 'أتراج', 'أوتلاندر', 'مونتيرو'],
              'شانجان': ['CS95', 'CS85', 'CS75', 'CS35', 'إيدو', 'يوني كي', 'يوني تي', 'يوني في'],
              'جيلي': ['أزكارا', 'كولراي', 'توجيلا', 'مونجارو', 'إمجراند', 'أوكافانجو'],
              'إم جي (MG)': ['MG 6', 'MG 5', 'MG RX5', 'MG HS', 'MG ZS', 'MG RX8', 'MG GT', 'MG ONE'],
              'هافال': ['H6', 'جوليان', 'دارجو', 'H9'],
              'دودج': ['تشارجر', 'تشالنجر', 'دورانجو', 'رام'],
              'جيب': ['جراند شيروكي', 'رانجلر', 'كومباس', 'جلاديتور'],
              'كرايسلر': ['300', 'باسيفيكا'],
              'كاديلاك': ['إسكاليد', 'CT5', 'CT6', 'XT5', 'XT6'],
              'لينكولن': ['نافيجيتور', 'أفياتور', 'نوتيلوس', 'كورسير'],
              'رينو': ['داستر', 'ميجان', 'كوليوس', 'سيمبول'],
              'بيجو': ['3008', '5008', '2008', '508', '208', 'بارتنر'],
              'فولكس واجن': ['طوارق', 'تيجوان', 'تيرامونت', 'جولف', 'باسات', 'أرتيون'],
              'سوزوكي': ['جيمني', 'سويفت', 'ديزاير', 'إرتيجا', 'فيتارا'],
              'أستون مارتن': ['DBX', 'فانتاج', 'DB11'],
              'فيراري': ['F8', 'SF90', 'روما', 'بورتوفينو', 'لوسو'],
              'لامبورجيني': ['أوروس', 'هوراكان', 'أفينتادور'],
              'ماكلارين': ['720S', '570S', 'GT', 'أرتورا'],
              'بنتلي': ['بنتايجا', 'فلاينج سبير', 'كونتيننتال'],
              'رولز رويس': ['كولينان', 'فانتوم', 'جوست', 'رايث'],
          };
          modelSelect.innerHTML = '<option value="">جميع الموديلات</option>';
          if (models[make]) {
              models[make].forEach(m => {
                  modelSelect.innerHTML += `<option value="${m}">${m}</option>`;
              });
              modelSelect.disabled = false;
          } else {
              modelSelect.disabled = true;
          }
      }
    </script>
  </div>
</div>

<!-- Info Modals (How it works etc) -->
<div id="hiwModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.8); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(5px); padding:20px; overflow-y:auto;">
  <div style="background:#fff; border-radius:var(--radius-lg); width:100%; max-width:500px; padding:30px; position:relative; box-shadow:0 20px 40px rgba(0,0,0,0.2); margin:auto; text-align:center;">
    <button onclick="document.getElementById('hiwModal').style.display='none'" style="position:absolute; top:20px; left:20px; background:#f1f5f9; border:none; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-dark); transition: background 0.3s;"><i class="ph ph-x"></i></button>
    <div id="hiwModalIcon" style="width:80px; height:80px; border-radius:50%; background:rgba(27,201,118,0.1); color:var(--primary); font-size:40px; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;"></div>
    <h3 id="hiwModalTitle" style="font-size:24px; font-weight:800; margin-bottom:15px; color:var(--text-dark);"></h3>
    <p id="hiwModalDesc" style="color:var(--text-muted); font-size:16px; line-height:1.8; margin-bottom:30px;"></p>
    <button onclick="document.getElementById('hiwModal').style.display='none'" class="btn btn-primary" style="width:100%; padding:14px; font-weight:700; border-radius:var(--radius-md);">حسناً، فهمت</button>
  </div>
</div>

<script>
const hiwData = {
  'buyer-reg': { icon: 'ph-identification-card', title: 'التسجيل والتوثيق', desc: 'لتتمكن من المشاركة في المزادات، يجب عليك التسجيل باستخدام الهوية الوطنية أو الإقامة. سيتم التحقق من بياناتك عبر منصة نفاذ الوطني الموحد لضمان أمان وموثوقية التعاملات.' },
  'buyer-wallet': { icon: 'ph-wallet', title: 'شحن المحفظة', desc: 'يتطلب دخول المزاد دفع "عربون دخول" مسترد. يمكنك شحن محفظتك بأمان عبر بطاقات مدى، فيزا، ماستركارد أو أبل باي. في حال لم يرسو عليك المزاد، سيتم إعادة المبلغ لمحفظتك تلقائياً.' },
  'buyer-bid': { icon: 'ph-gavel', title: 'بدء المزايدة', desc: 'يمكنك المزايدة يدوياً بنقرة زر، أو استخدام "المزايد الآلي" عبر تحديد الحد الأقصى لميزانيتك، وسيقوم النظام بالمزايدة نيابة عنك بشكل تدريجي للفوز بالمركبة بأقل سعر ممكن.' },
  'seller-reg': { icon: 'ph-buildings', title: 'تسجيل معرضك', desc: 'سجل معرضك أو حسابك كفرد لبيع مركباتك. ستحصل على لوحة تحكم متكاملة تتيح لك إضافة المركبات وتتبع المزادات وإدارة العوائد المالية بسهولة.' },
  'seller-wallet': { icon: 'ph-package', title: 'تجهيز المركبات', desc: 'قم بتصوير المركبة من جميع الزوايا وإرفاق تقارير الفحص الفني، كلما كانت المعلومات شفافة وصور المركبة واضحة زادت فرصة بيعها بسعر أعلى.' },
  'seller-list': { icon: 'ph-car', title: 'إطلاق المزاد', desc: 'حدد السعر الافتتاحي وتاريخ ووقت المزاد. سيقوم النظام بإرسال إشعارات للمشترين المهتمين لدخول المزاد لضمان بيع مركبتك بأفضل سعر.' }
};

function openHiwModal(type) {
  const data = hiwData[type];
  if(data) {
    document.getElementById('hiwModalIcon').innerHTML = `<i class="ph-fill ${data.icon}"></i>`;
    document.getElementById('hiwModalTitle').innerText = data.title;
    document.getElementById('hiwModalDesc').innerText = data.desc;
    document.getElementById('hiwModal').style.display = 'flex';
  }
}
</script>
