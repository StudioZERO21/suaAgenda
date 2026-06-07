// ============================================================
// suaAgenda.pro — Utilities, Mock Data & Theme System
// ============================================================

// ── COLOR PALETTES ──────────────────────────────────────────
window.SA_PALETTES = {
  A: {
    id: 'A', name: 'Cinza + Dourado',
    swatches: ['#1a1a1a', '#d4a574'],
    light: {
      primary: '#1a1a1a', primaryLight: '#2d2d2d',
      secondary: '#d4a574', secondaryLight: '#e6c299',
      bg: '#f5f5f5', surface: '#ffffff', surface2: '#fafafa',
      text1: '#1a1a1a', text2: '#5a5a5a', text3: '#999999',
      border: '#e2e2e2', border2: '#d0d0d0',
    },
    dark: {
      primary: '#d4a574', primaryLight: '#e6c299',
      secondary: '#d4a574', secondaryLight: '#e6c299',
      bg: '#0f0f0f', surface: '#1a1a1a', surface2: '#242424',
      text1: '#f0f0f0', text2: '#b0b0b0', text3: '#707070',
      border: '#2d2d2d', border2: '#3a3a3a',
    }
  },
  B: {
    id: 'B', name: 'Preto + Petróleo',
    swatches: ['#000000', '#1b4965'],
    light: {
      primary: '#000000', primaryLight: '#1e1e1e',
      secondary: '#1b4965', secondaryLight: '#2d7098',
      bg: '#f8f9fa', surface: '#ffffff', surface2: '#f0f4f8',
      text1: '#000000', text2: '#404040', text3: '#757575',
      border: '#d0d5dd', border2: '#bcc4cc',
    },
    dark: {
      primary: '#4a90e2', primaryLight: '#6aaaf5',
      secondary: '#2d7098', secondaryLight: '#4a90e2',
      bg: '#0a0e1a', surface: '#141c2a', surface2: '#1e2d40',
      text1: '#f0f2f5', text2: '#a0aec0', text3: '#637085',
      border: '#1e2d40', border2: '#2a3d55',
    }
  },
  C: {
    id: 'C', name: 'Preto + Azul Tech',
    swatches: ['#000000', '#0066ff'],
    light: {
      primary: '#000000', primaryLight: '#1a1a1a',
      secondary: '#0066ff', secondaryLight: '#3b82f6',
      bg: '#fafbfc', surface: '#ffffff', surface2: '#f0f5ff',
      text1: '#000000', text2: '#374151', text3: '#6b7280',
      border: '#e5e7eb', border2: '#d1d5db',
    },
    dark: {
      primary: '#3b82f6', primaryLight: '#60a5fa',
      secondary: '#0066ff', secondaryLight: '#3b82f6',
      bg: '#080f1c', surface: '#111827', surface2: '#1e2d42',
      text1: '#f0f4ff', text2: '#94a3b8', text3: '#64748b',
      border: '#1e2d42', border2: '#2d3f58',
    }
  },
  D: {
    id: 'D', name: 'Preto + Verde',
    swatches: ['#000000', '#10b981'],
    light: {
      primary: '#000000', primaryLight: '#1a1a1a',
      secondary: '#10b981', secondaryLight: '#34d399',
      bg: '#f0fdf8', surface: '#ffffff', surface2: '#ecfdf5',
      text1: '#000000', text2: '#374151', text3: '#6b7280',
      border: '#d1fae5', border2: '#a7f3d0',
    },
    dark: {
      primary: '#10b981', primaryLight: '#34d399',
      secondary: '#10b981', secondaryLight: '#34d399',
      bg: '#071912', surface: '#0d2218', surface2: '#14322a',
      text1: '#ecfdf5', text2: '#6ee7b7', text3: '#34d399',
      border: '#14322a', border2: '#1f4a3a',
    }
  },
  E: {
    id: 'E', name: 'Rosa + Branco',
    swatches: ['#ec4899', '#f9a8d4'],
    light: {
      primary: '#ec4899', primaryLight: '#f472b6',
      secondary: '#db2777', secondaryLight: '#ec4899',
      bg: '#fff1f8', surface: '#ffffff', surface2: '#fdf2f8',
      text1: '#18060e', text2: '#7c2d52', text3: '#b06075',
      border: '#fce7f3', border2: '#fbcfe8',
    },
    dark: {
      primary: '#f472b6', primaryLight: '#f9a8d4',
      secondary: '#ec4899', secondaryLight: '#f472b6',
      bg: '#180810', surface: '#2a1022', surface2: '#3a1830',
      text1: '#ffe8f5', text2: '#d4a0b8', text3: '#a06080',
      border: '#3a1830', border2: '#4a2040',
    }
  },
  F: {
    id: 'F', name: 'Areia + Terracota',
    swatches: ['#c2714f', '#e8d5b0'],
    light: {
      primary: '#c2714f', primaryLight: '#d4845f',
      secondary: '#a85c3a', secondaryLight: '#c2714f',
      bg: '#faf5ec', surface: '#fffdf8', surface2: '#f5ede0',
      text1: '#2c1a0e', text2: '#6b4c35', text3: '#a08060',
      border: '#e8d8c0', border2: '#d8c4a8',
    },
    dark: {
      primary: '#e09070', primaryLight: '#f0a880',
      secondary: '#c2714f', secondaryLight: '#d4845f',
      bg: '#1a0f08', surface: '#2a1a10', surface2: '#3a2418',
      text1: '#fdf0e0', text2: '#c8a888', text3: '#8a6848',
      border: '#3a2418', border2: '#4a3428',
    }
  },
  G: {
    id: 'G', name: 'Branco + Índigo',
    swatches: ['#4338ca', '#a5b4fc'],
    light: {
      primary: '#4338ca', primaryLight: '#4f46e5',
      secondary: '#6366f1', secondaryLight: '#818cf8',
      bg: '#f8f8ff', surface: '#ffffff', surface2: '#f0f0fe',
      text1: '#0f0f23', text2: '#3d3d5c', text3: '#7070a0',
      border: '#e0e0f8', border2: '#d0d0f0',
    },
    dark: {
      primary: '#818cf8', primaryLight: '#a5b4fc',
      secondary: '#6366f1', secondaryLight: '#818cf8',
      bg: '#08080f', surface: '#12121f', surface2: '#1e1e32',
      text1: '#f0f0ff', text2: '#a0a0c8', text3: '#606088',
      border: '#1e1e38', border2: '#2a2a48',
    }
  },
  H: {
    id: 'H', name: 'Creme + Âmbar',
    swatches: ['#92400e', '#fbbf24'],
    light: {
      primary: '#92400e', primaryLight: '#a85010',
      secondary: '#d97706', secondaryLight: '#f59e0b',
      bg: '#fffbf0', surface: '#ffffff', surface2: '#fef9e7',
      text1: '#1c0f04', text2: '#5c3a14', text3: '#9a6830',
      border: '#f0e0b0', border2: '#e8d098',
    },
    dark: {
      primary: '#fbbf24', primaryLight: '#fcd34d',
      secondary: '#d97706', secondaryLight: '#f59e0b',
      bg: '#120b00', surface: '#1e1200', surface2: '#2c1a00',
      text1: '#fff8e8', text2: '#d4a860', text3: '#907040',
      border: '#2c1a00', border2: '#3c2800',
    }
  },
  I: {
    id: 'I', name: 'Verde Harmony',
    swatches: ['#1e3d2b', '#6aaa7a'],
    light: {
      primary: '#1e3d2b', primaryLight: '#2a5239',
      secondary: '#6aaa7a', secondaryLight: '#8cc49a',
      bg: '#eef1e8', surface: '#f8faf5', surface2: '#e4eadc',
      text1: '#0f1e14', text2: '#2d4a35', text3: '#6a8a72',
      border: '#ccdec8', border2: '#b8d0b2',
    },
    dark: {
      primary: '#6aaa7a', primaryLight: '#8cc49a',
      secondary: '#4a8a5a', secondaryLight: '#6aaa7a',
      bg: '#080f0a', surface: '#101a12', surface2: '#18261b',
      text1: '#e8f4ea', text2: '#9abea0', text3: '#5a7a60',
      border: '#18261b', border2: '#22352a',
    }
  },
  J: {
    id: 'J', name: 'Preto + Laranja',
    swatches: ['#111111', '#f97316'],
    light: {
      primary: '#111111', primaryLight: '#222222',
      secondary: '#f97316', secondaryLight: '#fb923c',
      bg: '#fafaf9', surface: '#ffffff', surface2: '#f7f4ef',
      text1: '#0a0a0a', text2: '#3a3a3a', text3: '#808080',
      border: '#e4e0d8', border2: '#d0ccc4',
    },
    dark: {
      primary: '#f97316', primaryLight: '#fb923c',
      secondary: '#ea6c0a', secondaryLight: '#f97316',
      bg: '#0a0600', surface: '#140c00', surface2: '#1e1400',
      text1: '#fff4ec', text2: '#c8a080', text3: '#806040',
      border: '#1e1400', border2: '#2a1c00',
    }
  },
  K: {
    id: 'K', name: 'Preto + Roxo',
    swatches: ['#0a0a0a', '#7c3aed'],
    light: {
      primary: '#0a0a0a', primaryLight: '#1e1e1e',
      secondary: '#7c3aed', secondaryLight: '#9b5bf5',
      bg: '#faf9ff', surface: '#ffffff', surface2: '#f3f0ff',
      text1: '#0a0a0a', text2: '#3a3545', text3: '#7a7090',
      border: '#ddd8f0', border2: '#ccc4e8',
    },
    dark: {
      primary: '#9b5bf5', primaryLight: '#b47dff',
      secondary: '#7c3aed', secondaryLight: '#9b5bf5',
      bg: '#06040f', surface: '#0e0a1a', surface2: '#160f26',
      text1: '#f0ecff', text2: '#a898cc', text3: '#6858a0',
      border: '#160f26', border2: '#201535',
    }
  },
  L: {
    id: 'L', name: 'Branco + Roxo',
    swatches: ['#7c3aed', '#c4b5fd'],
    light: {
      primary: '#7c3aed', primaryLight: '#8b4cf5',
      secondary: '#5b21b6', secondaryLight: '#7c3aed',
      bg: '#fdfcff', surface: '#ffffff', surface2: '#f5f2ff',
      text1: '#1a0a3d', text2: '#3d2a6a', text3: '#7a6a9a',
      border: '#e4dcf8', border2: '#d4c8f4',
    },
    dark: {
      primary: '#c4b5fd', primaryLight: '#ddd6fe',
      secondary: '#9b5bf5', secondaryLight: '#c4b5fd',
      bg: '#06040f', surface: '#0e0a1a', surface2: '#160f26',
      text1: '#f0ecff', text2: '#b8a8e0', text3: '#7868a8',
      border: '#160f26', border2: '#201535',
    }
  },
};

// ── PROFESSIONALS ────────────────────────────────────────────
window.SA_PROFESSIONALS = [
  { id: 1, name: 'João Silva',    role: 'Barbeiro Sênior', initials: 'JS', color: '#1a1a1a', rating: 4.9, clients: 89 },
  { id: 2, name: 'Carlos Mendes', role: 'Barbeiro',        initials: 'CM', color: '#d4a574', rating: 4.7, clients: 62 },
  { id: 3, name: 'Ana Costa',     role: 'Colorista',       initials: 'AC', color: '#6366f1', rating: 4.8, clients: 54 },
];

// ── SERVICES ─────────────────────────────────────────────────
window.SA_SERVICES = [
  { id: 1, name: 'Corte',         duration: 30,  price: 45,  profIds: [1,2] },
  { id: 2, name: 'Barba',         duration: 30,  price: 35,  profIds: [1,2] },
  { id: 3, name: 'Corte + Barba', duration: 60,  price: 75,  profIds: [1,2] },
  { id: 4, name: 'Coloração',     duration: 120, price: 180, profIds: [3]   },
  { id: 5, name: 'Hidratação',    duration: 60,  price: 90,  profIds: [2,3] },
  { id: 6, name: 'Barba + Bigode',duration: 45,  price: 50,  profIds: [1,2] },
];

// ── CLIENTS ──────────────────────────────────────────────────
window.SA_CLIENTS = [
  { id:  1, name:'Miguel Santos',   email:'miguel@email.com',   phone:'(11) 98765-4321', status:'active',   total:12, lastDate:'2026-06-03' },
  { id:  2, name:'Pedro Oliveira',  email:'pedro@email.com',    phone:'(11) 97654-3210', status:'active',   total:8,  lastDate:'2026-05-28' },
  { id:  3, name:'Lucas Ferreira',  email:'lucas@email.com',    phone:'(11) 96543-2109', status:'active',   total:5,  lastDate:'2026-06-01' },
  { id:  4, name:'Rafael Costa',    email:'rafael@email.com',   phone:'(11) 95432-1098', status:'inactive', total:2,  lastDate:'2026-04-15' },
  { id:  5, name:'Bruno Lima',      email:'bruno@email.com',    phone:'(11) 94321-0987', status:'active',   total:15, lastDate:'2026-06-05' },
  { id:  6, name:'Marcelo Dias',    email:'marcelo@email.com',  phone:'(11) 93210-9876', status:'active',   total:7,  lastDate:'2026-05-30' },
  { id:  7, name:'Henrique Nunes',  email:'henrique@email.com', phone:'(11) 92109-8765', status:'active',   total:3,  lastDate:'2026-05-20' },
  { id:  8, name:'Thiago Cardoso',  email:'thiago@email.com',   phone:'(11) 91098-7654', status:'inactive', total:1,  lastDate:'2026-03-10' },
  { id:  9, name:'Rodrigo Alves',   email:'rodrigo@email.com',  phone:'(11) 90987-6543', status:'active',   total:9,  lastDate:'2026-06-04' },
  { id: 10, name:'Felipe Rocha',    email:'felipe@email.com',   phone:'(11) 89876-5432', status:'active',   total:6,  lastDate:'2026-05-25' },
  { id: 11, name:'Gabriel Souza',   email:'gabriel@email.com',  phone:'(11) 88765-4321', status:'active',   total:4,  lastDate:'2026-06-02' },
  { id: 12, name:'Eduardo Pinto',   email:'eduardo@email.com',  phone:'(11) 87654-3210', status:'active',   total:11, lastDate:'2026-06-06' },
];

// ── APPOINTMENTS (fixed, current week: June 2–8 2026) ────────
window.SA_TODAY = '2026-06-06';

(function() {
  const profs = window.SA_PROFESSIONALS;
  const svcs  = window.SA_SERVICES;
  const cls   = window.SA_CLIENTS;

  // [dayOffset from today(Fri=0), hour, min, clientIdx, profIdx, svcIdx, status]
  const raw = [
    // Mon Jun 2
    [-4,  8,  0,  0, 0, 0, 'confirmed'],
    [-4,  8,  0,  1, 1, 1, 'confirmed'],
    [-4,  9,  0,  2, 0, 2, 'confirmed'],
    [-4, 10, 30,  3, 1, 0, 'pending'],
    [-4, 11,  0,  4, 2, 4, 'confirmed'],
    [-4, 14,  0,  5, 0, 2, 'confirmed'],
    [-4, 15,  0,  6, 1, 5, 'confirmed'],
    [-4, 16,  0,  7, 0, 0, 'cancelled'],
    // Tue Jun 3
    [-3,  8, 30,  8, 0, 0, 'confirmed'],
    [-3,  9,  0,  9, 1, 2, 'confirmed'],
    [-3, 10,  0, 10, 2, 3, 'confirmed'],
    [-3, 13,  0, 11, 0, 1, 'confirmed'],
    [-3, 14,  0,  0, 1, 0, 'pending'],
    [-3, 15, 30,  1, 0, 2, 'confirmed'],
    // Wed Jun 4
    [-2,  8,  0,  2, 0, 0, 'confirmed'],
    [-2,  9, 30,  3, 2, 4, 'confirmed'],
    [-2, 11,  0,  4, 0, 2, 'confirmed'],
    [-2, 13, 30,  5, 1, 5, 'confirmed'],
    [-2, 15,  0,  6, 0, 0, 'pending'],
    [-2, 16, 30,  7, 2, 3, 'confirmed'],
    // Thu Jun 5
    [-1,  8,  0,  8, 1, 0, 'confirmed'],
    [-1,  9,  0,  9, 0, 2, 'confirmed'],
    [-1, 10, 30, 10, 2, 4, 'confirmed'],
    [-1, 13,  0, 11, 0, 1, 'pending'],
    [-1, 14, 30,  0, 1, 0, 'confirmed'],
    [-1, 16,  0,  1, 0, 2, 'confirmed'],
    // Fri Jun 6 (Today)
    [ 0,  8,  0,  2, 0, 0, 'confirmed'],
    [ 0,  8, 30,  3, 1, 1, 'confirmed'],
    [ 0,  9,  0,  4, 2, 3, 'confirmed'],
    [ 0, 10,  0,  5, 0, 2, 'confirmed'],
    [ 0, 11, 30,  6, 1, 0, 'pending'],
    [ 0, 13,  0,  7, 0, 2, 'confirmed'],
    [ 0, 14,  0,  8, 2, 4, 'confirmed'],
    [ 0, 15,  0,  9, 1, 0, 'confirmed'],
    [ 0, 16, 30, 10, 0, 5, 'pending'],
    // Sat Jun 7
    [ 1,  8,  0, 11, 0, 0, 'confirmed'],
    [ 1,  9,  0,  0, 1, 2, 'confirmed'],
    [ 1, 10,  0,  1, 0, 0, 'confirmed'],
    [ 1, 11,  0,  2, 1, 1, 'pending'],
    [ 1, 13,  0,  3, 0, 2, 'confirmed'],
  ];

  const today = new Date('2026-06-06T12:00:00');
  let id = 1;

  window.SA_APPOINTMENTS = raw.map(([dayOff, h, m, ci, pi, si, status]) => {
    const d = new Date(today);
    d.setDate(d.getDate() + dayOff);
    const dateStr = d.toISOString().split('T')[0];
    const prof = profs[pi], svc = svcs[si], client = cls[ci];
    return {
      id: id++,
      clientId: client.id, clientName: client.name,
      professionalId: prof.id, professionalName: prof.name, professionalColor: prof.color,
      serviceId: svc.id, serviceName: svc.name,
      price: svc.price, duration: svc.duration,
      date: dateStr, startHour: h, startMin: m, status,
    };
  });
})();

// ── HELPERS ──────────────────────────────────────────────────
window.SA_FMT = {
  currency: v => `R$ ${Number(v).toFixed(2).replace('.',',')}`,
  time: (h, m=0) => `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}`,
  date: s => { const [y,mo,d]=s.split('-'); return `${d}/${mo}/${y}`; },
  short: s => { const [,mo,d]=s.split('-'); return `${d}/${mo}`; },
  weekday: s => ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][new Date(s+'T12:00:00').getDay()],
  month: s => ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][new Date(s+'T12:00:00').getMonth()],
};

window.SA_GET_WEEK = function(dateStr) {
  const d = new Date(dateStr + 'T12:00:00');
  const day = d.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  const mon = new Date(d); mon.setDate(d.getDate() + diff);
  return Array.from({length:7}, (_,i) => {
    const dd = new Date(mon); dd.setDate(mon.getDate() + i);
    return dd.toISOString().split('T')[0];
  });
};

window.SA_APPLY_THEME = function(paletteId, isDark) {
  const p = window.SA_PALETTES[paletteId];
  const c = isDark ? p.dark : p.light;
  const r = document.documentElement;
  const set = (k,v) => r.style.setProperty(k,v);
  set('--sa-primary',       c.primary);
  set('--sa-primary-l',     c.primaryLight);
  set('--sa-secondary',     c.secondary);
  set('--sa-secondary-l',   c.secondaryLight);
  set('--sa-bg',            c.bg);
  set('--sa-surface',       c.surface);
  set('--sa-surface2',      c.surface2);
  set('--sa-text1',         c.text1);
  set('--sa-text2',         c.text2);
  set('--sa-text3',         c.text3);
  set('--sa-border',        c.border);
  set('--sa-border2',       c.border2);
  // Sidebar always dark in light mode
  if (isDark) {
    set('--sa-side-bg',   c.surface);
    set('--sa-side-text', c.text1);
    set('--sa-side-muted',c.text3);
  } else {
    set('--sa-side-bg',   '#111111');
    set('--sa-side-text', '#eeeeee');
    set('--sa-side-muted','#888888');
  }
  set('--sa-side-accent', c.secondary);
};
