import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { Home } from 'lucide-react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';

export default function NotFound() {
  const { i18n } = useTranslation();
  const isAr = i18n.language === 'ar';

  return (
    <div dir={isAr ? 'rtl' : 'ltr'} className="min-h-screen flex flex-col">
      <Navbar />
      <div className="flex-1 flex items-center justify-center pt-24 pb-16 px-4">
        <div className="text-center max-w-md">
          <div className="text-8xl font-bold text-primary/20 mb-4">404</div>
          <h1 className="text-3xl font-bold text-stone-900 mb-3">
            {isAr ? 'الصفحة غير موجودة' : 'Page Not Found'}
          </h1>
          <p className="text-stone-500 mb-8">
            {isAr ? 'عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.' : 'Sorry, the page you\'re looking for doesn\'t exist or has been moved.'}
          </p>
          <Link to="/" className="btn-primary inline-flex items-center gap-2">
            <Home className="w-4 h-4" />
            {isAr ? 'العودة للرئيسية' : 'Back to Home'}
          </Link>
        </div>
      </div>
      <Footer />
    </div>
  );
}
