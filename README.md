# Boyner Spor Ayakkabı Listesi

Bu proje, Boyner'in spor ayakkabı kategorisindeki ürünleri listelemek için geliştirilmiş bir web uygulamasıdır. Uygulama, Boyner API'si üzerinden ürün verilerini çeker ve kullanıcı dostu bir arayüzle sunar.

## Özellikler

- Sayfa numarasına göre ürün listeleme
- Belirli bir sayfa aralığındaki ürünleri paralel veya sıralı olarak getirme
- Ürün rozetlerine göre filtreleme (kampanya, yeni sezon, vb.)
- Duyarlı tasarım (responsive design)
- Ürün bilgilerinin detaylı gösterimi (marka, isim, fiyat, indirim, vb.)

## Netlify ile Canlıya Alma

Bu proje Netlify'da sunulmak üzere yapılandırılmıştır. Netlify Functions kullanılarak API çağrıları sunucu tarafında gerçekleştirilir.

### Netlify Dağıtım Adımları

1. GitHub'dan projeyi Netlify'a bağlayın
2. Herhangi bir build ayarı yapmanıza gerek yok, netlify.toml dosyası gerekli yapılandırmayı içermektedir
3. Dağıtım tamamlandıktan sonra site otomatik olarak yayınlanacaktır

## Geliştirme

Yerel ortamda geliştirme yapmak için:

1. Projeyi klonlayın
2. `npm install` komutunu çalıştırarak bağımlılıkları yükleyin
3. Yerel geliştirme için [Netlify CLI](https://docs.netlify.com/cli/get-started/) kullanabilirsiniz:
   ```
   npm install netlify-cli -g
   netlify dev
   ```

## Teknolojiler

- HTML, CSS, JavaScript
- Bootstrap 5
- Node.js (Netlify Functions)
- Netlify Sunucusuz Fonksiyonlar 