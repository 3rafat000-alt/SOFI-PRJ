import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Suspense, lazy, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { AuthProvider } from './auth/AuthContext';
import { ChatProvider } from './context/ChatContext';
import ProtectedRoute from './components/guards/ProtectedRoute';

// Lazy-loaded pages — each becomes own chunk
const Home = lazy(() => import('./pages/Home'));
const Properties = lazy(() => import('./pages/Properties'));
const PropertyDetail = lazy(() => import('./pages/PropertyDetail'));
const About = lazy(() => import('./pages/About'));
const Contact = lazy(() => import('./pages/Contact'));
const Login = lazy(() => import('./pages/Login'));
const Register = lazy(() => import('./pages/Register'));
const AgencyRegister = lazy(() => import('./pages/AgencyRegister'));
const ForgotPassword = lazy(() => import('./pages/ForgotPassword'));
const ResetPassword = lazy(() => import('./pages/ResetPassword'));
const AgentMatching = lazy(() => import('./pages/AgentMatching'));
const Profile = lazy(() => import('./pages/Profile'));
const Agencies = lazy(() => import('./pages/Agencies'));
const AgencyDetail = lazy(() => import('./pages/AgencyDetail'));
const SearchResults = lazy(() => import('./pages/SearchResults'));
const NotFound = lazy(() => import('./pages/NotFound'));
// Install wizard
const InstallWizard = lazy(() => import('./pages/install/InstallWizard'));
// Admin
const AdminLayout = lazy(() => import('./pages/admin/AdminLayout'));
const AdminHome = lazy(() => import('./pages/admin/AdminHome'));
const AdminUsers = lazy(() => import('./pages/admin/AdminUsers'));
const AdminAgenciesPage = lazy(() => import('./pages/admin/AdminAgencies'));
const AdminProperties = lazy(() => import('./pages/admin/AdminProperties'));
const AdminPlans = lazy(() => import('./pages/admin/AdminPlans'));
const AdminMessages = lazy(() => import('./pages/admin/AdminMessages'));
const AdminReviews = lazy(() => import('./pages/admin/AdminReviews'));
const AdminAreas = lazy(() => import('./pages/admin/AdminAreas'));
const AdminSettings = lazy(() => import('./pages/admin/AdminSettings'));
// Agency
const DashboardLayout = lazy(() => import('./pages/agency/DashboardLayout'));
const DashboardHome = lazy(() => import('./pages/agency/DashboardHome'));
const AgencyProperties = lazy(() => import('./pages/agency/AgencyProperties'));
const PropertyCreate = lazy(() => import('./pages/agency/PropertyCreate'));
const PropertyEdit = lazy(() => import('./pages/agency/PropertyEdit'));
const AgencyAgents = lazy(() => import('./pages/agency/AgencyAgents'));
const AgencyInquiries = lazy(() => import('./pages/agency/AgencyInquiries'));
const AgencyDeals = lazy(() => import('./pages/agency/AgencyDeals'));
const AgencyCommission = lazy(() => import('./pages/agency/AgencyCommission'));
const AgencySubscription = lazy(() => import('./pages/agency/AgencySubscription'));
const AgencyProfile = lazy(() => import('./pages/agency/AgencyProfile'));
const AgencyChat = lazy(() => import('./pages/agency/AgencyChat'));

// User dashboard
const UserLayout = lazy(() => import('./pages/user/UserLayout'));
const UserHome = lazy(() => import('./pages/user/UserHome'));
const UserFavorites = lazy(() => import('./pages/user/UserFavorites'));
const UserInquiries = lazy(() => import('./pages/user/UserInquiries'));
const UserSearches = lazy(() => import('./pages/user/UserSearches'));
const UserProfile = lazy(() => import('./pages/user/UserProfile'));
const UserChat = lazy(() => import('./pages/user/UserChat'));

/** Minimal loading shell while page chunks load */
function PageLoader() {
  return (
    <div className="min-h-screen bg-[#f8f6f3]">
      <div className="h-16 bg-white border-b border-stone-200" />
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="flex items-center gap-3 text-stone-400">
          <div className="w-5 h-5 rounded-full border-2 border-stone-300 border-t-stone-600 animate-spin" />
          <span className="text-sm">جاري التحميل…</span>
        </div>
      </div>
    </div>
  );
}

export default function App() {
  const { i18n } = useTranslation();

  useEffect(() => {
    const dir = i18n.language === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.dir = dir;
    document.documentElement.lang = i18n.language;
  }, [i18n.language]);

  return (
    <BrowserRouter>
      <AuthProvider>
        <ChatProvider>
          <Suspense fallback={<PageLoader />}>
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/properties" element={<Properties />} />
              <Route path="/properties/:slug" element={<PropertyDetail />} />
              <Route path="/about" element={<About />} />
              <Route path="/contact" element={<Contact />} />
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/register/agency" element={<AgencyRegister />} />
              <Route path="/forgot-password" element={<ForgotPassword />} />
              <Route path="/reset-password" element={<ResetPassword />} />
              <Route path="/profile" element={<Profile />} />
              <Route path="/agencies" element={<Agencies />} />
              <Route path="/agencies/:slug" element={<AgencyDetail />} />
              <Route path="/search" element={<SearchResults />} />
              <Route path="/matching" element={<AgentMatching />} />
              {/* Admin routes */}
              <Route path="/admin" element={
                <ProtectedRoute roles={['admin']}><AdminLayout /></ProtectedRoute>
              }>
                <Route index element={<AdminHome />} />
                <Route path="users" element={<AdminUsers />} />
                <Route path="agencies" element={<AdminAgenciesPage />} />
                <Route path="properties" element={<AdminProperties />} />
                <Route path="plans" element={<AdminPlans />} />
                <Route path="messages" element={<AdminMessages />} />
                <Route path="reviews" element={<AdminReviews />} />
                <Route path="areas" element={<AdminAreas />} />
                <Route path="settings" element={<AdminSettings />} />
              </Route>

              {/* Agency routes */}
              <Route path="/dashboard" element={
                <ProtectedRoute roles={['agency', 'admin']}><DashboardLayout /></ProtectedRoute>
              }>
                <Route index element={<DashboardHome />} />
                <Route path="properties" element={<AgencyProperties />} />
                <Route path="properties/new" element={<PropertyCreate />} />
                <Route path="properties/:id/edit" element={<PropertyEdit />} />
                <Route path="agents" element={<AgencyAgents />} />
                <Route path="inquiries" element={<AgencyInquiries />} />
                <Route path="deals" element={<AgencyDeals />} />
                <Route path="commission" element={<AgencyCommission />} />
                <Route path="subscription" element={<AgencySubscription />} />
                <Route path="profile" element={<AgencyProfile />} />
                <Route path="chat" element={<AgencyChat />} />
              </Route>

              {/* User dashboard routes */}
              <Route path="/user" element={
                <ProtectedRoute><UserLayout /></ProtectedRoute>
              }>
                <Route path="dashboard" element={<UserHome />} />
                <Route path="favorites" element={<UserFavorites />} />
                <Route path="inquiries" element={<UserInquiries />} />
                <Route path="searches" element={<UserSearches />} />
                <Route path="profile" element={<UserProfile />} />
                <Route path="chat" element={<UserChat />} />
              </Route>

              {/* Install wizard (no auth required) */}
              <Route path="/install" element={<InstallWizard />} />

              <Route path="*" element={<NotFound />} />
            </Routes>
          </Suspense>
        </ChatProvider>
      </AuthProvider>
    </BrowserRouter>
  );
}
