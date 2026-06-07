// ============================================================
// suaAgenda.pro — Main App Router + Theme
// ============================================================
const { useState, useEffect } = React;

const TWEAK_DEFAULTS = /*EDITMODE-BEGIN*/{
  "palette": "A",
  "darkMode": false,
  "bodyFont": "inter",
  "headingFont": "poppins"
}/*EDITMODE-END*/;

function App() {
  const [t, setTweak]     = useTweaks(TWEAK_DEFAULTS);
  const [screen, setScreen]   = useState('login');
  const [appointments, setAppointments] = useState(window.SA_APPOINTMENTS);
  const [bookingData, setBookingData]   = useState(null);
  const [profileOpen, setProfileOpen]   = useState(false);
  const [companyOpen, setCompanyOpen]   = useState(false);

  const palette = t.palette || 'A';
  const dark    = t.darkMode || false;

  // Apply CSS vars + fonts on change
  const BODY_FONTS = { inter:"'Inter',-apple-system", 'dm-sans':"'DM Sans'", nunito:"'Nunito'", lato:"'Lato'" };
  const HEAD_FONTS = { poppins:"'Poppins'", montserrat:"'Montserrat'", jakarta:"'Plus Jakarta Sans'", 'dm-serif':"'DM Serif Display'" };

  useEffect(() => {
    window.SA_APPLY_THEME(palette, dark);
    const bodyF = BODY_FONTS[t.bodyFont]   || "'Inter'";
    const headF = HEAD_FONTS[t.headingFont] || "'Poppins'";
    document.documentElement.style.setProperty('--sa-font-body',    `${bodyF}, sans-serif`);
    document.documentElement.style.setProperty('--sa-font-heading',  `${headF}, sans-serif`);
  }, [palette, dark, t.bodyFont, t.headingFont]);

  const handleLogin  = () => setScreen('dashboard');
  const handleNewAppt = (data) => setBookingData(data || true);

  const handleSaveAppt = (appt) => {
    setAppointments(prev => [...prev, appt]);
    setBookingData(null);
    window.SA_TOAST(`Agendamento criado! ${appt.clientName} — ${SA_FMT.time(appt.startHour, appt.startMin)}`, 'success');
  };

  // ── LOGIN ────────────────────────────────────────────────
  if (screen === 'login') {
    return (
      <>
        <LoginScreen onLogin={handleLogin} />
        <TweaksPanel>
          <TweakSection label="Paleta" />
          <TweakSelect label="Tema de Cores" value={palette}
            options={Object.values(SA_PALETTES).map(p => ({ value: p.id, label: p.name }))}
            onChange={v => setTweak('palette', v)} />
          <TweakToggle label="Modo Escuro" value={dark} onChange={v => setTweak('darkMode', v)} />
        </TweaksPanel>
        <ToastCont />
      </>
    );
  }

  // ── PUBLIC PAGE ──────────────────────────────────────────
  if (screen === 'public') {
    return (
      <>
        <PublicScreen onBack={() => setScreen('dashboard')} />
        <TweaksPanel>
          <TweakSection label="Aparência" />
          <TweakSelect label="Paleta" value={palette}
            options={Object.values(SA_PALETTES).map(p => ({ value: p.id, label: p.name }))}
            onChange={v => setTweak('palette', v)} />
          <TweakToggle label="Modo Escuro" value={dark} onChange={v => setTweak('darkMode', v)} />
        </TweaksPanel>
        <ToastCont />
      </>
    );
  }

  // ── MAIN APP ─────────────────────────────────────────────
  return (
    <>
      <AppShell
        screen={screen}
        onNavigate={setScreen}
        dark={dark}
        onToggleDark={() => setTweak('darkMode', !dark)}
        onNewAppt={handleNewAppt}
        onProfileClick={() => setProfileOpen(true)}
        onCompanyClick={() => setCompanyOpen(true)}
      >
        {screen === 'dashboard' && (
          <DashboardScreen
            appointments={appointments}
            onNavigate={setScreen}
            onNewAppt={handleNewAppt}
          />
        )}
        {screen === 'calendar' && (
          <CalendarScreen
            appointments={appointments}
            setAppointments={setAppointments}
            onNewAppt={handleNewAppt}
          />
        )}
        {screen === 'clients'     && <ClientsScreen />}
        {screen === 'staff'       && <StaffScreen />}
        {screen === 'financial'   && <FinancialScreen />}
        {screen === 'reports'     && <ReportsScreen />}
        {screen === 'portfolio'   && <PortfolioScreen />}
        {screen === 'products'    && <ProductsScreen />}
        {screen === 'pos'         && <POSScreen />}
        {screen === 'services'    && <ServicesScreen />}
        {screen === 'roles'       && <RolesScreen />}
        {screen === 'plans'       && <PlansScreen />}
        {screen === 'permissions' && <PermissionsScreen />}
        {screen === 'site'        && <SiteSettingsScreen />}
        {screen === 'settings'    && (
          <SettingsScreen
            palette={palette}
            dark={dark}
            onPaletteChange={v => setTweak('palette', v)}
            onDarkChange={v => setTweak('darkMode', v)}
          />
        )}

        {/* Public page quick-access */}
        <div style={{ position: 'fixed', bottom: 28, left: 260, zIndex: 200 }}>
          <button onClick={() => setScreen('public')} style={{
            display: 'flex', alignItems: 'center', gap: 8,
            padding: '10px 18px', borderRadius: 24,
            background: 'var(--sa-surface)', border: '1.5px solid var(--sa-border)',
            boxShadow: '0 4px 14px rgba(0,0,0,.1)', cursor: 'pointer',
            fontSize: 13, fontWeight: 600, color: 'var(--sa-text2)',
            fontFamily: "'Inter',sans-serif", transition: 'all 200ms ease',
          }}>
            <Icon name="globe" size={15} style={{ color: 'var(--sa-secondary)' }} />
            Página Pública
          </button>
        </div>
      </AppShell>

      <BookingModal
        open={!!bookingData}
        initialData={bookingData}
        onClose={() => setBookingData(null)}
        onSave={handleSaveAppt}
      />

      <TweaksPanel>
        <TweakSection label="Paleta" />
        <TweakSelect label="Tema de Cores" value={palette}
          options={Object.values(SA_PALETTES).map(p => ({ value: p.id, label: p.name }))}
          onChange={v => setTweak('palette', v)} />
        <TweakToggle label="Modo Escuro" value={dark} onChange={v => setTweak('darkMode', v)} />
        <TweakSection label="Tipografia" />
        <TweakSelect label="Fonte Corpo" value={t.bodyFont || 'inter'}
          options={[{value:'inter',label:'Inter'},{value:'dm-sans',label:'DM Sans'},{value:'nunito',label:'Nunito'},{value:'lato',label:'Lato'}]}
          onChange={v => setTweak('bodyFont', v)} />
        <TweakSelect label="Fonte Títulos" value={t.headingFont || 'poppins'}
          options={[{value:'poppins',label:'Poppins'},{value:'montserrat',label:'Montserrat'},{value:'jakarta',label:'Plus Jakarta Sans'},{value:'dm-serif',label:'DM Serif Display'}]}
          onChange={v => setTweak('headingFont', v)} />
      </TweaksPanel>

      <ProfileModal open={profileOpen} onClose={() => setProfileOpen(false)} />
      <CompanyModal open={companyOpen} onClose={() => setCompanyOpen(false)} />

      <ToastCont />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
