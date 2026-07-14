import { useEffect, useState } from 'react';
import { Routes, Route, useNavigate, useLocation, Navigate, Outlet } from 'react-router-dom';
import { Text, Center, Loader } from '@mantine/core';
import { useDisclosure, useMediaQuery } from '@mantine/hooks';

import { useAuthStore } from '@/modules/auth/authStore';
import { useOnlineStatus } from '@/shared/hooks/useOnlineStatus';
import { AuthModal, AuthWall, DemoBanner } from '@/modules/auth/AuthModal';
import { AppHeader } from './AppHeader';
import { TefteleLogo } from './TefteleLogo';

// Eventor
import { SectionsSidenav } from '@/modules/eventor/components/SectionsSidenav/SectionsSidenav';
import { EventorToolbar } from '@/modules/eventor/components/Toolbar/EventorToolbar';
import { EventEditor } from '@/modules/eventor/components/EventEditor/EventEditor';
import { ReadModal } from '@/modules/eventor/components/ReadModal/ReadModal';
import { SectionsManager } from '@/modules/eventor/components/SectionsManager/SectionsManager';
import { FlowView } from '@/modules/eventor/views/FlowView/FlowView';
import { GridCalendar } from '@/modules/eventor/views/GridCalendar/GridCalendar';
import { SearchPanel } from '@/modules/eventor/views/SearchPanel/SearchPanel';
import { DraftsView } from '@/modules/eventor/views/DraftsView/DraftsView';
import { EventPublicPage } from '@/modules/eventor/views/EventPublicPage/EventPublicPage';
import { PinboardButton } from '@/modules/eventor/components/Pinboard/Pinboard';

// Ledger
import { AccountsSidenav } from '@/modules/ledger/components/AccountsSidenav/AccountsSidenav';
import { TimelineView }    from '@/modules/ledger/views/TimelineView/TimelineView';
import { StatsView }       from '@/modules/ledger/views/StatsView/StatsView';
import { CategoryManager } from '@/modules/ledger/views/CategoryManager/CategoryManager';
import '@/modules/ledger/ledger.css';
import { TransactionReadModal } from '@/modules/ledger/components/TransactionReadModal/TransactionReadModal';
import { TransactionEditor } from '@/modules/ledger/components/TransactionEditor/TransactionEditor';

// Stuffer
import { StufferSidenav } from '@/modules/stuffer/components/StufferSidenav/StufferSidenav';
import { ThingsView } from '@/modules/stuffer/views/ThingsView/ThingsView';
import { FeedView } from '@/modules/stuffer/views/FeedView/FeedView';
import { ThingPage } from '@/modules/stuffer/components/ThingPage/ThingPage';
import { ThingEditor } from '@/modules/stuffer/components/ThingEditor/ThingEditor';
import { RegisterModal } from '@/modules/stuffer/components/RegisterModal/RegisterModal';
import { LocationsManager } from '@/modules/stuffer/components/LocationsManager/LocationsManager';

// Booker
import { BookerSidenav } from '@/modules/booker/components/BookerSidenav/BookerSidenav';
import { LibraryView } from '@/modules/booker/views/LibraryView/LibraryView';
import { BookView } from '@/modules/booker/views/BookView/BookView';
import { DocView } from '@/modules/booker/views/DocView/DocView';
import { BookEditor } from '@/modules/booker/components/BookEditor/BookEditor';
import '@/modules/booker/booker.css';

// Exploiter
import { ExploiterSidenav } from '@/modules/exploiter/components/ExploiterSidenav/ExploiterSidenav';
import { ExploiterToolbar } from '@/modules/exploiter/components/Toolbar/ExploiterToolbar';
import { TimelineView as ExploiterTimeline } from '@/modules/exploiter/views/TimelineView/TimelineView';
import { EventEditor as ExploiterEventEditor } from '@/modules/exploiter/components/EventEditor/EventEditor';
import { EventReadModal as ExploiterReadModal } from '@/modules/exploiter/components/EventReadModal/EventReadModal';
import '@/modules/exploiter/exploiter.css';

// Home
import { HomeFeed } from '@/modules/home/views/HomeFeed/HomeFeed';
import '@/modules/home/home.css';

const ComingSoon = ({ name }) => (
  <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100%' }}>
    <Text c="dimmed" size="sm">{name} — coming soon</Text>
  </div>
);

// ── Лэйауты модулей ───────────────────────────────────────────────

const EventorLayout = ({ sidebarCollapsed, mobileSidebarOpen, onMobileClose }) => {
  const isOnline = useOnlineStatus();
  return (
    <>
      <SectionsSidenav collapsed={sidebarCollapsed} mobileOpen={mobileSidebarOpen} onMobileClose={onMobileClose} />
      <div className="main-content">
        {!isOnline && (
          <div className="offline-banner">
            <span>●</span>
            No internet connection — events will be saved as local drafts
          </div>
        )}
        <EventorToolbar />
        <Outlet />
      </div>
    </>
  );
};

const LedgerLayout = ({ sidebarCollapsed, mobileSidebarOpen, onMobileClose }) => (
  <>
    <AccountsSidenav collapsed={sidebarCollapsed} mobileOpen={mobileSidebarOpen} onMobileClose={onMobileClose} />
    <div className="main-content"><Outlet /></div>
  </>
);

const StufferLayout = ({ sidebarCollapsed, mobileSidebarOpen, onMobileClose }) => (
  <>
    <StufferSidenav collapsed={sidebarCollapsed} mobileOpen={mobileSidebarOpen} onMobileClose={onMobileClose} />
    <div className="main-content"><Outlet /></div>
    <ThingEditor />
    <RegisterModal />
    <LocationsManager />
  </>
);

const BookerLayout = ({ sidebarCollapsed, mobileSidebarOpen, onMobileClose }) => (
  <>
    <BookerSidenav collapsed={sidebarCollapsed} mobileOpen={mobileSidebarOpen} onMobileClose={onMobileClose} />
    <div className="main-content"><Outlet /></div>
    <BookEditor />
  </>
);

const ExploiterLayout = ({ sidebarCollapsed, mobileSidebarOpen, onMobileClose }) => (
  <>
    <ExploiterSidenav collapsed={sidebarCollapsed} mobileOpen={mobileSidebarOpen} onMobileClose={onMobileClose} />
    <div className="main-content">
      <ExploiterToolbar />
      <Outlet />
    </div>
    <ExploiterEventEditor />
    <ExploiterReadModal />
  </>
);

// ══════════════════════════════════════════════════════════════════
// PUBLIC SHELL — без авторизации, без хедера, чистая страница
// Роуты: /e/:id, /b/:id, /s/:id, /p/:id
// ══════════════════════════════════════════════════════════════════
function PublicApp() {
  return (
    <Routes>
      <Route path="/e/:id" element={<EventPublicPage />} />
      {/* /b/:id, /s/:id, /p/:id — добавим когда будут готовы */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

// ══════════════════════════════════════════════════════════════════
// AUTH SHELL — авторизованная часть приложения
// ══════════════════════════════════════════════════════════════════
function AuthApp() {
  const navigate = useNavigate();
  const location = useLocation();
  const [authOpened, { open: openAuth, close: closeAuth }] = useDisclosure(false);
  const isMobile = useMediaQuery('(max-width: 768px)');

  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

  const handleToggleSidebar = () => {
    if (isMobile) setMobileSidebarOpen((v) => !v);
    else setSidebarCollapsed((v) => !v);
  };

  useEffect(() => { setMobileSidebarOpen(false); }, [location.pathname]);

  useEffect(() => {
    if (location.pathname === '/' || location.pathname === '/eventor' || location.pathname === '/eventor/') {
      navigate('/eventor/flow', { replace: true });
    }
    if (location.pathname === '/ledger' || location.pathname === '/ledger/') {
      navigate('/ledger/timeline', { replace: true });
    }
    if (location.pathname === '/stuffer' || location.pathname === '/stuffer/') {
      navigate('/stuffer/things', { replace: true });
    }
    if (location.pathname === '/booker' || location.pathname === '/booker/') {
      navigate('/booker/library', { replace: true });
    }
    if (location.pathname === '/exploiter' || location.pathname === '/exploiter/') {
      navigate('/exploiter/timeline', { replace: true });
    }
  }, [location.pathname, navigate]);

  return (
    <div className="app-shell">
      <DemoBanner />
      <AppHeader onToggleSidebar={handleToggleSidebar} authModalOpen={openAuth} />

      <div className="app-body">
        {isMobile && mobileSidebarOpen && (
          <div className="sidebar-overlay" onClick={() => setMobileSidebarOpen(false)} />
        )}
        {isMobile && !mobileSidebarOpen && (
          <div className="sidebar-rail" onClick={() => setMobileSidebarOpen(true)} />
        )}

        <Routes>
          <Route path="/" element={<div className="main-content"><HomeFeed /></div>} />

          <Route path="/eventor" element={
            <EventorLayout
              sidebarCollapsed={!isMobile && sidebarCollapsed}
              mobileSidebarOpen={isMobile && mobileSidebarOpen}
              onMobileClose={() => setMobileSidebarOpen(false)}
            />
          }>
            <Route index element={<Navigate to="flow" replace />} />
            <Route path="flow"     element={<FlowView />} />
            <Route path="calendar" element={<GridCalendar />} />
            <Route path="search"   element={<SearchPanel />} />
            <Route path="drafts"   element={<DraftsView />} />
          </Route>

          <Route path="/ledger" element={
            <LedgerLayout
              sidebarCollapsed={!isMobile && sidebarCollapsed}
              mobileSidebarOpen={isMobile && mobileSidebarOpen}
              onMobileClose={() => setMobileSidebarOpen(false)}
            />
          }>
            <Route index element={<Navigate to="timeline" replace />} />
            <Route path="timeline"   element={<TimelineView />} />
            <Route path="stats"      element={<StatsView />} />
            <Route path="categories" element={<CategoryManager />} />
          </Route>

          <Route path="/stuffer" element={
            <StufferLayout
              sidebarCollapsed={!isMobile && sidebarCollapsed}
              mobileSidebarOpen={isMobile && mobileSidebarOpen}
              onMobileClose={() => setMobileSidebarOpen(false)}
            />
          }>
            <Route index element={<Navigate to="things" replace />} />
            <Route path="things"     element={<ThingsView />} />
            <Route path="things/:id" element={<ThingPage />} />
            <Route path="feed"       element={<FeedView />} />
          </Route>

          <Route path="/booker" element={
            <BookerLayout
              sidebarCollapsed={!isMobile && sidebarCollapsed}
              mobileSidebarOpen={isMobile && mobileSidebarOpen}
              onMobileClose={() => setMobileSidebarOpen(false)}
            />
          }>
            <Route index element={<Navigate to="library" replace />} />
            <Route path="library" element={<LibraryView />} />
            <Route path=":bookId" element={<BookView />} />
            <Route path=":bookId/:docId" element={<DocView />} />
          </Route>

          <Route path="/exploiter" element={
            <ExploiterLayout
              sidebarCollapsed={!isMobile && sidebarCollapsed}
              mobileSidebarOpen={isMobile && mobileSidebarOpen}
              onMobileClose={() => setMobileSidebarOpen(false)}
            />
          }>
            <Route index element={<Navigate to="timeline" replace />} />
            <Route path="timeline" element={<ExploiterTimeline />} />
          </Route>
          <Route path="/tasker/*"    element={<div className="main-content"><ComingSoon name="Tasker" /></div>} />
          <Route path="/pm/*"        element={<div className="main-content"><ComingSoon name="Project Manager" /></div>} />
        </Routes>
      </div>

      {/* Глобальные оверлеи */}
      <AuthModal opened={authOpened} onClose={closeAuth} />
      <ReadModal />
      <EventEditor />
      <SectionsManager />
      <TransactionEditor />
      <TransactionReadModal />
      <PinboardButton />
    </div>
  );
}

// ══════════════════════════════════════════════════════════════════
// ROOT — роутер верхнего уровня
// Решает: публичный shell или авторизованный
// ══════════════════════════════════════════════════════════════════
const PUBLIC_PREFIXES = ['/e/', '/b/', '/s/', '/p/'];

export default function App() {
  const user = useAuthStore((s) => s.user);
  const isChecked = useAuthStore((s) => s.isChecked);
  const isKnownBrowser = useAuthStore((s) => s.isKnownBrowser);
  const checkAuth = useAuthStore((s) => s.checkAuth);
  const location = useLocation();

  useEffect(() => { checkAuth(); }, [checkAuth]);

  // Определяем тип роута до любых проверок авторизации
  const isPublicRoute = PUBLIC_PREFIXES.some((p) => location.pathname.startsWith(p));

  // Публичный shell — рендерим сразу, не ждём checkAuth
  if (isPublicRoute) {
    return <PublicApp />;
  }

  // Ждём проверки авторизации
  if (!isChecked) {
    return (
      <Center style={{ width: '100vw', height: '100vh' }}>
        <Loader size="lg" />
      </Center>
    );
  }

  // Незнакомый браузер — AuthWall
  if (!user && !isKnownBrowser) {
    return <AuthWall />;
  }

  // Авторизованный shell
  return <AuthApp />;
}
