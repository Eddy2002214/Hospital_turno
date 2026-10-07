'use client'

import { FormEvent, useMemo, useState, useEffect } from 'react'
import { ArrowLeft, CalendarDays, ClipboardList, Hospital, LogIn, Menu, Printer, Save, ShieldCheck, Users, X } from 'lucide-react'

type Shift = 'M' | 'M*' | 'T' | 'N' | 'L'
type View = 'login' | 'dashboard' | 'editor' | 'reports' | 'report'

type RoleMeta = { year: string; month: string; service: string; serviceId: string | null }

type Person = {
  id: number
  no: string
  ff: string
  item: string
  bio: string
  phone: string
  name: string
  role: string
  shifts: Shift[]
}

type User = {
  id_usuario: number
  nombre: string
  rol: string
  id_departamento: number | null
}

const days = Array.from({ length: 31 }, (_, index) => index + 1)
const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']

function getTotals(shifts: Shift[]) {
  return { M: shifts.filter((shift) => shift === 'M' || shift === 'M*').length, T: shifts.filter((shift) => shift === 'T').length, N: shifts.filter((shift) => shift === 'N').length, L: shifts.filter((shift) => shift === 'L').length }
}

const getApiUrl = (path: string) => {
    const base = window.location.pathname.replace(/\/$/, '');
    return `${base}${path}`;
};

function AppShell({ view, setView, children, userObj }: { view: View; setView: (view: View) => void; children: React.ReactNode; userObj: User }) {
  const [open, setOpen] = useState(false)
  if (view === 'login') return <>{children}</>
  return <div className="app-layout"><aside className={open ? 'sidebar open' : 'sidebar'}><div className="brand"><div className="brand-mark"><Hospital size={20} /></div><div><strong>HOSPITAL JAPONÉS</strong><span>Gestión de turnos</span></div><button className="mobile-close" onClick={() => setOpen(false)} aria-label="Cerrar menú"><X size={18} /></button></div><nav aria-label="Navegación principal"><button className={view === 'dashboard' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('dashboard'); setOpen(false) }}><ClipboardList size={18} /> Resumen</button>
  {userObj.rol !== 'Operador' && (
      <button className={view === 'editor' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('editor'); setOpen(false) }}><CalendarDays size={18} /> Registrar rol mensual</button>
  )}
  <button className={view === 'reports' || view === 'report' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('reports'); setOpen(false) }}><Printer size={18} /> Roles existentes</button></nav><div className="sidebar-footer" style={{ marginTop: 'auto' }}><div className="user-avatar">{userObj.nombre.slice(0, 1).toUpperCase()}</div><div><strong>{userObj.nombre}</strong><span>{userObj.rol}</span></div><button className="logout" onClick={() => setView('login')}>Salir</button></div></aside><div className="main-area"><header className="topbar"><button className="menu-button" onClick={() => setOpen(true)} aria-label="Abrir menú"><Menu size={20} /></button><div><span className="eyebrow">CONTROL DE ROLES</span><strong>Panel Principal</strong></div><div className="topbar-status"><span className="status-dot" /> Sesión activa</div></header>{children}</div></div>
}

function Login({ onLogin }: { onLogin: (code: string) => void }) {
  const [code, setCode] = useState('')
  const [error, setError] = useState('')
  const submit = async (event: FormEvent) => { 
      event.preventDefault(); 
      if (code.trim()) {
          try {
              const res = await fetch(getApiUrl('/api/login?code=') + encodeURIComponent(code.trim()));
              if (!res.ok) {
                  setError('Código inválido o usuario no encontrado.');
                  return;
              }
              const data = await res.json();
              onLogin(data);
          } catch(e) {
              setError('Error de conexión.');
          }
      } 
  }
  return <main className="login-screen"><div className="login-card"><div className="login-logo"><Hospital size={27} /></div><span className="eyebrow">HOSPITAL JAPONÉS · SISTEMA INTERNO</span><h1>Control de roles</h1><p>Ingresa tu código biométrico para continuar.<br/><small>(Ej: ADM-001, JEF-001, USR-TI-001)</small></p><form onSubmit={submit}><label htmlFor="access-code">Código biométrico</label><input id="access-code" autoFocus value={code} onChange={(event) => {setCode(event.target.value); setError('');}} placeholder="Ej. JEF-001" autoComplete="off" />{error && <p style={{color: 'red', marginTop: '5px', fontSize: '14px'}}>{error}</p>}<button type="submit"><LogIn size={17} /> Ingresar al sistema</button></form><div className="login-help"><ShieldCheck size={15} /> Acceso protegido para personal autorizado</div></div></main>
}

function Dashboard({ setView, userObj, people }: { setView: (view: View) => void; userObj: User; people: Person[] }) {
  return <main className="content"><div className="welcome"><div><span className="eyebrow">PANEL PRINCIPAL</span><h1>Buenos días, {userObj.nombre}</h1><p>Administra la programación mensual de turnos de tu servicio.</p></div><div className="date-chip"><CalendarDays size={17} /><span>Octubre 2026</span></div></div><div className="stats-grid"><div className="stat-card"><span className="stat-icon blue"><Users size={18} /></span><div><strong>{people.length}</strong><span>Personal registrado</span></div></div><div className="stat-card"><span className="stat-icon green"><CalendarDays size={18} /></span><div><strong>Octubre</strong><span>Rol en edición</span></div></div><div className="stat-card"><span className="stat-icon violet"><ClipboardList size={18} /></span><div><strong>31 días</strong><span>Período configurado</span></div></div></div><section className="action-section"><div className="section-heading"><div><span className="eyebrow">ACCIONES RÁPIDAS</span><h2>¿Qué deseas hacer?</h2></div></div><div className="action-grid">
  {userObj.rol !== 'Operador' && (
      <button className="action-card primary" onClick={() => setView('editor')}><span className="action-icon"><CalendarDays size={24} /></span><span><strong>Registrar nuevo rol mensual</strong><small>Configura el personal y asigna los turnos del mes.</small></span><span className="arrow">→</span></button>
  )}
  <button className="action-card" onClick={() => setView('reports')}><span className="action-icon"><Printer size={24} /></span><span><strong>Ver e imprimir roles existentes</strong><small>Consulta, revisa e imprime planillas ya registradas.</small></span><span className="arrow">→</span></button></div></section><section className="recent-section"><div className="section-heading"><div><span className="eyebrow">ACTIVIDAD RECIENTE</span><h2>Roles registrados</h2></div><button className="text-button" onClick={() => setView('reports')}>Ver historial →</button></div><div className="recent-row"><div className="recent-mark"><ClipboardList size={18} /></div><div><strong>{userObj.rol === 'Administrador' ? 'Múltiples Servicios' : 'Tu Servicio Asignado'}</strong><span>Octubre 2026 · {people.length} funcionarios · Actualizado hoy</span></div><span className="badge">En revisión</span></div></section></main>
}

function Editor({ setView, people, setPeople, role, setRole, userObj }: { setView: (view: View) => void; people: Person[]; setPeople: React.Dispatch<React.SetStateAction<Person[]>>; role: RoleMeta; setRole: React.Dispatch<React.SetStateAction<RoleMeta>>, userObj: User }) {
  const [year, setYear] = useState(role.year)
  const [month, setMonth] = useState(role.month)
  const [service, setService] = useState(role.service)
  const [depts, setDepts] = useState<any[]>([])
  const [turnos, setTurnos] = useState<any[]>([])

  useEffect(() => {
      fetch(getApiUrl('/api/departamentos')).then(r=>r.json()).then(data => {
          setDepts(data)
          if(userObj.id_departamento) {
              const myDept = data.find((d:any) => d.id_departamento === userObj.id_departamento);
              if(myDept) {
                  setService(myDept.nombre_departmento);
                  setRole(r => ({...r, service: myDept.nombre_departmento, serviceId: String(myDept.id_departamento)}));
              }
          } else if (userObj.rol === 'Administrador') {
              setService('Todos los departamentos');
              setRole(r => ({...r, service: 'Todos los departamentos', serviceId: 'todos'}));
          }
      })
      fetch(getApiUrl('/api/turnos')).then(r=>r.json()).then(data => setTurnos(data));
  }, [userObj])

  const onServiceChange = (e: any) => {
      setService(e.target.value)
      if (e.target.value === 'Todos los departamentos') {
          setRole(r => ({...r, serviceId: 'todos', service: 'Todos los departamentos'}))
      } else {
          const d = depts.find(x => x.nombre_departmento === e.target.value)
          if(d) {
              setRole(r => ({...r, serviceId: String(d.id_departamento), service: d.nombre_departmento}))
          }
      }
  }

  const daysInMonth = new Date(parseInt(year), monthNames.indexOf(month) + 1, 0).getDate();
  const days = Array.from({ length: daysInMonth }, (_, i) => i + 1);

  const updateShift = (personIndex: number, dayIndex: number, value: Shift) => setPeople((current) => current.map((person, index) => index === personIndex ? { ...person, shifts: person.shifts.map((shift, i) => i === dayIndex ? value : shift) } : person))
  
  const fillRow = (personIndex: number) => {
      setPeople(current => current.map((p, index) => {
          if (index === personIndex) {
              const firstShift = p.shifts[0] || 'L';
              return { ...p, shifts: p.shifts.map(() => firstShift) };
          }
          return p;
      }));
  }
  
  const fillRandom = () => {
      const shiftTypes: Shift[] = ['M', 'M*', 'T', 'N', 'L'];
      setPeople((current) => current.map(person => ({
          ...person,
          shifts: person.shifts.map(() => shiftTypes[Math.floor(Math.random() * shiftTypes.length)])
      })));
  };

  const loadPreviousMonth = async () => {
      let mIndex = monthNames.indexOf(month);
      let prevM = mIndex === 0 ? 12 : mIndex;
      let prevY = mIndex === 0 ? parseInt(year) - 1 : parseInt(year);
      
      let qs = `?mes=${prevM}&anio=${prevY}`;
      if (role.serviceId && role.serviceId !== 'todos') qs += `&departamento_id=${role.serviceId}`;
      else if (!role.serviceId && userObj.id_departamento) qs += `&departamento_id=${userObj.id_departamento}`;
      
      const res = await fetch(getApiUrl('/api/empleados') + qs);
      const data = await res.json();
      
      setPeople(current => current.map(p => {
          const prevPerson = data.find((d: any) => d.id === p.id);
          if (prevPerson) {
              return { ...p, shifts: prevPerson.shifts };
          }
          return p;
      }));
  }

  const calcHours = (shifts: Shift[]) => {
      let total = 0;
      shifts.forEach(s => {
          const t = turnos.find(x => x.codigo_turno === s);
          if (t && t.hora_entrada && t.hora_salida) {
              const start = new Date(`1970-01-01T${t.hora_entrada}`);
              const end = new Date(`1970-01-01T${t.hora_salida}`);
              let diff = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
              if (diff < 0) diff += 24; // cruzó medianoche
              total += diff;
          }
      });
      return total;
  }
  
  const save = async () => { 
      try {
          const res = await fetch(getApiUrl('/api/roles/save'), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  year,
                  month,
                  serviceId: role.serviceId,
                  people
              })
          });
          if (res.ok) {
              setRole({ year, month, service, serviceId: role.serviceId }); 
              setView('report');
          } else {
              const data = await res.json();
              alert(data.error || 'Error al guardar el rol');
          }
      } catch (e) {
          alert('Error de red al intentar guardar');
      }
  }
  
  return <main className="content editor-page"><div className="page-heading"><div><span className="eyebrow">NUEVO REGISTRO</span><h1>Registrar rol mensual</h1><p>Completa la matriz de turnos para generar la planilla oficial.</p></div><button className="secondary-button" onClick={() => setView('dashboard')}><ArrowLeft size={16} /> Volver al resumen</button></div><section className="form-card"><div className="form-card-heading"><div><h2>Datos del período</h2><p>Define el período y servicio al que corresponde este rol.</p></div><span className="step-label">PASO 1 DE 2</span></div><div className="period-form"><label>Año<select value={year} onChange={(event) => setYear(event.target.value)}><option>2026</option><option>2027</option><option>2028</option></select></label><label>Mes<select value={month} onChange={(event) => setMonth(event.target.value)}>{monthNames.map((name) => <option key={name}>{name}</option>)}</select></label><label className="service-field">Departamento / Servicio<select value={service} onChange={onServiceChange} disabled={userObj.rol !== 'Administrador'} >
      {userObj.rol === 'Administrador' && <option value="Todos los departamentos">Todos los departamentos</option>}
      {depts.map(d => <option key={d.id_departamento} value={d.nombre_departmento}>{d.nombre_departmento}</option>)}
      {depts.length === 0 && <option>{service}</option>}
  </select></label></div></section><section className="form-card matrix-card"><div className="form-card-heading"><div><h2>Matriz de captura masiva</h2><p>Selecciona el turno correspondiente en cada día para cada funcionario.</p></div><span className="step-label">PASO 2 DE 2</span></div><div className="shift-legend"><span><i className="shift-m" /> M · Mañana</span><span><i className="shift-mstar" /> M* · Extendido</span><span><i className="shift-t" /> T · Tarde</span><span><i className="shift-n" /> N · Noche</span><span><i className="shift-l" /> L · Libre</span></div><div className="editor-table-wrap" style={{ maxHeight: '600px', overflowY: 'auto' }}><table className="editor-table"><thead><tr><th className="sticky-col first" style={{ zIndex: 4 }}>N°</th><th className="sticky-col person-col" style={{ zIndex: 4 }}>Personal</th>{days.map((day) => <th key={day}>{day}</th>)}<th>Total Hrs</th></tr></thead><tbody>{people.map((person, personIndex) => <tr key={person.bio}><td className="sticky-col first">{personIndex+1}</td><td className="sticky-col person-col"><strong>{person.name}</strong><span>{person.role}</span></td>{days.map((day, dayIndex) => <td key={`${person.bio}-${dayIndex}`} onDoubleClick={() => fillRow(personIndex)} title="Doble clic para llenar todo el mes con este turno"><select aria-label={`${person.name}, día ${dayIndex + 1}`} value={person.shifts[dayIndex] || 'L'} onChange={(event) => updateShift(personIndex, dayIndex, event.target.value as Shift)} className={`shift-select shift-${(person.shifts[dayIndex] || 'L').replace('*', 'star').toLowerCase()}`}><option value="M">M</option><option value="M*">M*</option><option value="T">T</option><option value="N">N</option><option value="L">L</option></select></td>)}<td style={{ fontWeight: 'bold' }}>{calcHours(person.shifts.slice(0, daysInMonth))}h</td></tr>)}</tbody></table></div><div className="editor-actions"><span><ShieldCheck size={15} /> Los cambios se guardan al procesar el rol.</span><div style={{ display: 'flex', gap: '10px' }}><button className="secondary-button" onClick={loadPreviousMonth}>Copiar del mes anterior</button><button className="secondary-button" onClick={fillRandom}>Llenar al azar</button><button className="primary-button" onClick={save}><Save size={16} /> Guardar y procesar rol</button></div></div></section></main>
}

function Reports({ setView, people, role, setRole, userObj }: { setView: (view: View) => void; people: Person[]; role: RoleMeta; setRole: React.Dispatch<React.SetStateAction<RoleMeta>>; userObj: User }) {
  const [selectedYear, setSelectedYear] = useState(role.year);
  const [selectedMonth, setSelectedMonth] = useState(role.month);
  
  const handleView = () => {
      setRole({ ...role, year: selectedYear, month: selectedMonth });
      setView('report');
  }

  return <main className="content"><div className="page-heading"><div><span className="eyebrow">ARCHIVO DE ROLES</span><h1>Roles existentes</h1><p>Consulta los registros mensuales y genera una copia para impresión.</p></div>
  {userObj.rol !== 'Operador' && (
      <button className="primary-button" onClick={() => setView('editor')}><CalendarDays size={16} /> Registrar nuevo rol</button>
  )}
  </div><section className="report-list-card"><div className="list-toolbar" style={{ display: 'flex', gap: '15px' }}>
      <select value={selectedYear} onChange={(e) => setSelectedYear(e.target.value)} className="search-placeholder" style={{ width: '120px' }}>
          <option>2026</option><option>2027</option><option>2028</option>
      </select>
      <select value={selectedMonth} onChange={(e) => setSelectedMonth(e.target.value)} className="search-placeholder" style={{ width: '150px' }}>
          {monthNames.map(m => <option key={m}>{m}</option>)}
      </select>
      <div className="search-placeholder" style={{ opacity: 0.7 }}>{role.service}</div>
  </div><div className="report-row"><div className="report-icon"><ClipboardList size={20} /></div><div className="report-info"><strong>{role.service}</strong><span>Rol mensual · {selectedMonth} {selectedYear}</span></div><span className="badge green-badge">Procesado</span><button className="icon-button" onClick={handleView} aria-label="Ver rol"><Printer size={17} /></button></div></section></main>
}

function Report({ setView, people, role }: { setView: (view: View) => void; people: Person[]; role: RoleMeta }) {
  const daysInMonth = new Date(parseInt(role.year), monthNames.indexOf(role.month) + 1, 0).getDate();
  const dynamicDays = Array.from({ length: daysInMonth }, (_, i) => i + 1);

  return <main className="report-page"><div className="report-toolbar"><button className="secondary-button" onClick={() => setView('reports')}><ArrowLeft size={16} /> Volver a roles</button><button className="primary-button" onClick={() => window.print()}><Printer size={16} /> Imprimir rol</button></div><article className="print-sheet"><header className="roster-header"><div className="institution-block"><div className="hospital-name">HOSPITAL JAPONÉS</div><div className="received-box">SELLO DE RECEPCIÓN<br /><span>FECHA: ____ / ____ / ______</span></div></div><div className="title-block"><h1>ROL DE TURNO MENSUAL</h1><p>{role.service.toUpperCase()}</p></div><div className="period-box"><span>PERÍODO</span><strong>MES: {role.month.toUpperCase()}</strong><strong>AÑO: {role.year}</strong></div></header><section className="legend-row"><div className="legend-items"><b>LEYENDA:</b><span><strong>M</strong> = Mañana 07:30 a 13:30</span><span><strong>M*</strong> = Mañana extendido</span><span><strong>T</strong> = Tarde</span><span><strong>N</strong> = Noche</span><span><strong>L</strong> = Libre</span></div></section><div className="table-wrap"><table className="roster-table"><caption className="sr-only">Rol de turno mensual</caption><colgroup><col className="col-no" /><col className="col-ff" /><col className="col-item" /><col className="col-bio" /><col className="col-phone" /><col className="col-name" /><col className="col-role" />{dynamicDays.map((day) => <col key={day} className="col-day" />)}{[1, 2, 3, 4, 5].map((item) => <col key={item} className="col-total" />)}</colgroup><thead><tr className="group-row"><th colSpan={7}>DATOS DEL PERSONAL</th><th colSpan={daysInMonth}>DÍAS DEL MES — {role.month.toUpperCase()} {role.year}</th><th colSpan={5}>TOTALES</th></tr><tr className="header-row"><th>N°</th><th>F.<br />FIN.</th><th>ITEM</th><th>COD.<br />BIOMÉTRICO</th><th>TELF.</th><th>NOMBRE</th><th>CARGO</th>{dynamicDays.map((day) => <th key={day}>{day}</th>)}<th>MAÑANA</th><th>TARDE</th><th>NOCHE</th><th>LIBRE</th><th>TOTAL</th></tr></thead><tbody>{people.map((person, i) => { const totals = getTotals(person.shifts.slice(0, daysInMonth)); return <tr key={person.bio}><td>{i+1}</td><td>{person.ff}</td><td>{person.item}</td><td>{person.bio}</td><td>{person.phone}</td><td className="name-cell">{person.name}</td><td className="role-cell">{person.role}</td>{person.shifts.slice(0, daysInMonth).map((shift, index) => <td className={`shift-cell ${shift === 'L' ? 'free' : ''}`} key={`${person.bio}-${index}`}>{shift === 'L' ? '' : shift}</td>)}<td className="total-cell">{totals.M}</td><td className="total-cell">{totals.T}</td><td className="total-cell">{totals.N}</td><td className="total-cell">{totals.L}</td><td className="total-cell">{daysInMonth}</td></tr> })}</tbody><tfoot><tr><td colSpan={7}>TOTAL PERSONAL PROGRAMADO: {people.length}</td><td colSpan={daysInMonth}></td><td colSpan={5}></td></tr></tfoot></table></div><footer className="signatures"><div>ELABORADO POR:<span /></div><div>REVISADO POR:<span /></div><div>APROBADO POR:<span /></div></footer></article></main>
}

export default function Page() {
  const [view, setView] = useState<View>('login')
  const [userObj, setUserObj] = useState<User | null>(null)
  const [people, setPeople] = useState<Person[]>([])
  
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear().toString();
  const [role, setRole] = useState<RoleMeta>({ year: currentYear, month: monthNames[currentMonth], service: 'Cargando...', serviceId: null })
  
  useEffect(() => {
      if(userObj) {
          // fetch people based on service
          const m = monthNames.indexOf(role.month) + 1;
          let qs = `?mes=${m}&anio=${role.year}`;
          if (role.serviceId && role.serviceId !== 'todos') qs += `&departamento_id=${role.serviceId}`;
          else if (!role.serviceId && userObj.id_departamento) qs += `&departamento_id=${userObj.id_departamento}`;
          
          fetch(getApiUrl('/api/empleados') + qs).then(r=>r.json()).then(data => {
              setPeople(data);
          });
      }
  }, [role.serviceId, role.month, role.year, userObj]);

  const current = useMemo(() => view, [view])
  if (current === 'login' || !userObj) return <Login onLogin={(u: any) => { setUserObj(u); setView('dashboard') }} />
  return <AppShell view={current} setView={setView} userObj={userObj}>{current === 'dashboard' && <Dashboard setView={setView} userObj={userObj} people={people} />}{current === 'editor' && <Editor setView={setView} people={people} setPeople={setPeople} role={role} setRole={setRole} userObj={userObj} />}{current === 'reports' && <Reports setView={setView} people={people} role={role} setRole={setRole} userObj={userObj} />}{current === 'report' && <Report setView={setView} people={people} role={role} />}</AppShell>
}
