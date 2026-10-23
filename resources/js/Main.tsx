'use client'

import { FormEvent, useMemo, useState, useEffect, Fragment } from 'react'
import { ArrowLeft, CalendarDays, ClipboardList, Hospital, LogIn, Menu, Printer, Save, ShieldCheck, Users, X, Archive, Search, Copy, RotateCcw, FolderOpen } from 'lucide-react'

type Shift = 'M' | 'M*' | 'T' | 'N' | 'L'
type View = 'login' | 'dashboard' | 'editor' | 'reports' | 'report' | 'history' | 'assign' | 'areas'

type RoleMeta = { year: string; month: string; service: string; serviceId: string | null }

type ExternalShift = {
  dept: string
  code: string
  name: string
}

type Person = {
  id: number
  no: string
  ff: string
  item: string
  bio: string
  phone: string
  name: string
  role: string
  dept_id?: number | null
  dept_name?: string
  is_jefe?: boolean
  shifts: Shift[]
  external_shifts?: (ExternalShift | null)[]
}

type User = {
  id_usuario: number
  id_empleado: number | null
  nombre: string
  rol: string
  id_departamento: number | null
}

const days = Array.from({ length: 31 }, (_, index) => index + 1)
const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']

function shiftsOverlap(codeA: Shift, codeB: Shift): boolean {
  if (codeA === 'L' || codeB === 'L') return false
  if (codeA === codeB) return true
  if (codeA === 'M*' && (codeB === 'M' || codeB === 'T')) return true
  if (codeB === 'M*' && (codeA === 'M' || codeA === 'T')) return true
  return false
}

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
      <>
          <button className={view === 'editor' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('editor'); setOpen(false) }}><CalendarDays size={18} /> Registrar rol mensual</button>
          <button className={view === 'assign' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('assign'); setOpen(false) }}><Users size={18} /> Asignar personal</button>
      </>
  )}
  {userObj.rol === 'Administrador' && (
      <button className={view === 'areas' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('areas'); setOpen(false) }}><Hospital size={18} /> Gestión de Áreas</button>
  )}
  <button className={view === 'reports' || view === 'report' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('reports'); setOpen(false) }}><Printer size={18} /> Roles existentes</button>
  <button className={view === 'history' ? 'nav-item active' : 'nav-item'} onClick={() => { setView('history'); setOpen(false) }}><Archive size={18} /> Historia</button></nav><div className="sidebar-footer" style={{ marginTop: 'auto' }}><div className="user-avatar">{userObj.nombre.slice(0, 1).toUpperCase()}</div><div><strong>{userObj.nombre}</strong><span>{userObj.rol}</span></div><button className="logout" onClick={() => setView('login')}>Salir</button></div></aside><div className="main-area"><header className="topbar"><button className="menu-button" onClick={() => setOpen(true)} aria-label="Abrir menú"><Menu size={20} /></button><div><span className="eyebrow">CONTROL DE ROLES</span><strong>Panel Principal</strong></div><div className="topbar-status"><span className="status-dot" /> Sesión activa</div></header>{children}</div></div>
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

function Dashboard({ setView, userObj, people, role }: { setView: (view: View) => void; userObj: User; people: Person[]; role: RoleMeta }) {
  return <main className="content"><div className="welcome"><div><span className="eyebrow">PANEL PRINCIPAL</span><h1>Buenos días, {userObj.nombre}</h1><p>Administra la programación mensual de turnos de tu servicio.</p></div><div className="date-chip"><CalendarDays size={17} /><span>{role.month} {role.year}</span></div></div><div className="stats-grid"><div className="stat-card"><span className="stat-icon blue"><Users size={18} /></span><div><strong>{people.length}</strong><span>Personal registrado</span></div></div><div className="stat-card"><span className="stat-icon green"><CalendarDays size={18} /></span><div><strong>{role.month}</strong><span>Rol en edición</span></div></div><div className="stat-card"><span className="stat-icon violet"><ClipboardList size={18} /></span><div><strong>31 días</strong><span>Período configurado</span></div></div></div><section className="action-section"><div className="section-heading"><div><span className="eyebrow">ACCIONES RÁPIDAS</span><h2>¿Qué deseas hacer?</h2></div></div><div className="action-grid">
  {userObj.rol !== 'Operador' && (
      <>
          <button className="action-card primary" onClick={() => setView('editor')}><span className="action-icon"><CalendarDays size={24} /></span><span><strong>Registrar nuevo rol mensual</strong><small>Configura el personal y asigna los turnos del mes.</small></span><span className="arrow">→</span></button>
          <button className="action-card" onClick={() => setView('assign')}><span className="action-icon"><Users size={24} /></span><span><strong>Asignar nuevo personal</strong><small>Recluta empleados sin área a tu departamento.</small></span><span className="arrow">→</span></button>
      </>
  )}
  <button className="action-card" onClick={() => setView('reports')}><span className="action-icon"><Printer size={24} /></span><span><strong>Ver e imprimir roles existentes</strong><small>Consulta, revisa e imprime planillas ya registradas.</small></span><span className="arrow">→</span></button></div></section><section className="recent-section"><div className="section-heading"><div><span className="eyebrow">ACTIVIDAD RECIENTE</span><h2>Roles registrados</h2></div><button className="text-button" onClick={() => setView('reports')}>Ver historial →</button></div><div className="recent-row"><div className="recent-mark"><ClipboardList size={18} /></div><div><strong>{userObj.rol === 'Administrador' ? 'Múltiples Servicios' : 'Tu Servicio Asignado'}</strong><span>{role.month} {role.year} · {people.length} funcionarios · Actualizado hoy</span></div><span className="badge">En revisión</span></div></section></main>
}

function Editor({ setView, people, setPeople, role, setRole, userObj }: { setView: (view: View) => void; people: Person[]; setPeople: React.Dispatch<React.SetStateAction<Person[]>>; role: RoleMeta; setRole: React.Dispatch<React.SetStateAction<RoleMeta>>, userObj: User }) {
  const [year, setYear] = useState(role.year)
  const [month, setMonth] = useState(role.month)
  const [service, setService] = useState(role.service)
  const [depts, setDepts] = useState<any[]>([])
  const [turnos, setTurnos] = useState<any[]>([])
  const [alertMsg, setAlertMsg] = useState('')
  const [deptAllStaff, setDeptAllStaff] = useState<any[]>([])

  useEffect(() => {
      setYear(role.year);
      setMonth(role.month);
  }, [role.year, role.month]);

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

  const currentDept = useMemo(() => {
      if (service === 'Todos los departamentos') return null;
      return depts.find(d => d.nombre_departmento === service) || null;
  }, [service, depts]);

  const currentServiceId = useMemo(() => {
      if (service === 'Todos los departamentos') return 'todos';
      if (currentDept) return String(currentDept.id_departamento);
      if (role.serviceId) return String(role.serviceId);
      if (userObj.id_departamento) return String(userObj.id_departamento);
      return null;
  }, [service, currentDept, role.serviceId, userObj.id_departamento]);

  useEffect(() => {
      if (userObj.rol === 'Administrador') return;
      const m = monthNames.indexOf(month) + 1;
      let qs = `?mes=${m}&anio=${year}`;
      if (currentServiceId && currentServiceId !== 'todos') qs += `&departamento_id=${currentServiceId}`;
      else if (!currentServiceId && userObj.id_departamento) qs += `&departamento_id=${userObj.id_departamento}`;

      fetch(getApiUrl('/api/empleados/all') + qs).then(r=>r.json()).then(data => {
          if (Array.isArray(data)) setDeptAllStaff(data);
      }).catch(() => {});
  }, [year, month, currentServiceId, userObj]);

  const { ownStaff, sharedStaff } = useMemo(() => {
      const currentDeptId = currentServiceId && currentServiceId !== 'todos' 
          ? currentServiceId 
          : userObj.id_departamento;

      const unassignedToRole = deptAllStaff.filter(emp => {
          const alreadyInRole = people.some(p => p.id === emp.id_empleado);
          const notAdmin = emp.cargo?.nombre_cargo !== 'Administrador de Sistema';
          return !alreadyInRole && notAdmin;
      });

      const own = unassignedToRole.filter(emp => 
          currentDeptId ? String(emp.id_departamento) === String(currentDeptId) : true
      );
      const shared = unassignedToRole.filter(emp => 
          currentDeptId ? String(emp.id_departamento) !== String(currentDeptId) : false
      );

      return { ownStaff: own, sharedStaff: shared };
  }, [deptAllStaff, currentServiceId, userObj, people]);

  const addEmployee = (empId: number) => {
      const emp = deptAllStaff.find(e => e.id_empleado === empId);
      if (!emp) return;
      const isJefe = emp.cargo && ['Jefe de Servicio', 'Jefe de Area'].includes(emp.cargo.nombre_cargo);

      const targetDeptId = currentDept ? currentDept.id_departamento : (userObj.id_departamento || emp.id_departamento);
      const targetDeptName = currentDept ? currentDept.nombre_departmento : (userObj.id_departamento ? service : (emp.departamento?.nombre_departmento || service));

      const newPerson: Person = {
          id: emp.id_empleado,
          no: String(emp.id_empleado),
          ff: 'TGN',
          item: emp.item_id || '101',
          bio: emp.codigo_biometrico,
          phone: emp.telefono || '',
          name: emp.nombre_completo,
          role: emp.cargo ? emp.cargo.nombre_cargo : 'Operador / Especialista',
          dept_id: targetDeptId,
          dept_name: targetDeptName,
          is_jefe: isJefe,
          shifts: Array(31).fill('L'),
          external_shifts: emp.external_shifts || Array(31).fill(null),
      };
      setPeople(prev => {
          const list = [...prev, newPerson];
          return list.sort((a, b) => {
              const rankA = a.is_jefe ? 2 : 0;
              const rankB = b.is_jefe ? 2 : 0;
              if (rankA !== rankB) return rankB - rankA;
              return a.name.localeCompare(b.name);
          });
      });
  };

  const addAllDepartmentStaff = () => {
      if (ownStaff.length === 0) return;
      const targetDeptId = currentDept ? currentDept.id_departamento : (userObj.id_departamento || null);
      const targetDeptName = currentDept ? currentDept.nombre_departmento : service;

      const newPeople: Person[] = ownStaff.map(emp => ({
          id: emp.id_empleado,
          no: String(emp.id_empleado),
          ff: 'TGN',
          item: emp.item_id || '101',
          bio: emp.codigo_biometrico,
          phone: emp.telefono || '',
          name: emp.nombre_completo,
          role: emp.cargo ? emp.cargo.nombre_cargo : 'Operador / Especialista',
          dept_id: targetDeptId || emp.id_departamento,
          dept_name: targetDeptName,
          is_jefe: emp.cargo && ['Jefe de Servicio', 'Jefe de Area'].includes(emp.cargo.nombre_cargo),
          shifts: Array(31).fill('L'),
          external_shifts: emp.external_shifts || Array(31).fill(null),
      }));
      setPeople(prev => {
          const list = [...prev, ...newPeople];
          return list.sort((a, b) => {
              const rankA = a.is_jefe ? 2 : 0;
              const rankB = b.is_jefe ? 2 : 0;
              if (rankA !== rankB) return rankB - rankA;
              return a.name.localeCompare(b.name);
          });
      });
  };

  const removePerson = (personId: number) => {
      setPeople(prev => prev.filter(p => p.id !== personId));
  };

  const resetToOnlyJefe = () => {
      setPeople(prev => {
          const jefes = prev.filter(p => p.is_jefe);
          const base = jefes.length > 0 ? jefes : prev.slice(0, 1);
          return base.map(j => ({ ...j, shifts: Array(31).fill('L') }));
      });
  };

  const handleYearChange = (newYear: string) => {
      setYear(newYear);
      setRole(r => ({ ...r, year: newYear }));
  };

  const handleMonthChange = (newMonth: string) => {
      setMonth(newMonth);
      setRole(r => ({ ...r, month: newMonth }));
  };

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

  const updateShift = (personId: number, dayIndex: number, value: Shift) => {
      const person = people.find(p => p.id === personId);
      if (person && person.external_shifts && person.external_shifts[dayIndex]) {
          const ext = person.external_shifts[dayIndex];
          if (value !== 'L' && ext && shiftsOverlap(value, ext.code as Shift)) {
              setAlertMsg(`Conflicto de horario: ${person.name} ya tiene programado el turno ${ext.code} (${ext.name}) en el área "${ext.dept}" el día ${dayIndex + 1}. No puede asignarse en el mismo horario.`);
              return;
          }
      }
      setPeople((current) => current.map((person) => person.id === personId ? { ...person, shifts: person.shifts.map((shift, i) => i === dayIndex ? value : shift) } : person));
  };
  
  const fillRow = (personId: number, startDayIndex: number = 0) => {
      const person = people.find(p => p.id === personId);
      if (!person) return;
      const shiftToCopy = person.shifts[startDayIndex] || 'L';
      let skippedCount = 0;

      setPeople(current => current.map((p) => {
          if (p.id === personId) {
              return {
                  ...p,
                  shifts: p.shifts.map((s, i) => {
                      if (i < startDayIndex) return s;
                      if (shiftToCopy !== 'L' && p.external_shifts && p.external_shifts[i]) {
                          const ext = p.external_shifts[i];
                          if (ext && shiftsOverlap(shiftToCopy, ext.code as Shift)) {
                              skippedCount++;
                              return 'L';
                          }
                      }
                      return shiftToCopy;
                  })
              };
          }
          return p;
      }));

      if (skippedCount > 0) {
          setAlertMsg(`Atención: Se aplicó el turno "${shiftToCopy}", pero se mantuvo en Libre (L) durante ${skippedCount} día(s) donde ${person.name} ya tiene turno programado en otra área para evitar conflicto de horario.`);
      }
  };

  const groupedPeople = useMemo(() => {
      const groups: { [key: string]: { deptName: string; members: Person[] } } = {};
      people.forEach((person) => {
          const key = person.dept_name || 'Personal Sin Área Asignada';
          if (!groups[key]) {
              groups[key] = {
                  deptName: key,
                  members: []
              };
          }
          groups[key].members.push(person);
      });
      return Object.values(groups);
  }, [people]);
  
  const clearAllShifts = () => {
      setPeople((current) => current.map(person => ({
          ...person,
          shifts: Array(31).fill('L')
      })));
  };

  const loadPreviousMonth = async () => {
      let mIndex = monthNames.indexOf(month);
      let prevM = mIndex === 0 ? 12 : mIndex;
      let prevY = mIndex === 0 ? parseInt(year) - 1 : parseInt(year);
      let prevMonthName = monthNames[prevM - 1];
      
      let qs = `?mes=${prevM}&anio=${prevY}`;
      if (role.serviceId && role.serviceId !== 'todos') qs += `&departamento_id=${role.serviceId}`;
      else if (!role.serviceId && userObj.id_departamento) qs += `&departamento_id=${userObj.id_departamento}`;
      
      try {
          const res = await fetch(getApiUrl('/api/empleados') + qs);
          const data = await res.json();
          
          if (!data || data.length === 0) {
              setAlertMsg(`No se encontró personal registrado en el mes anterior (${prevMonthName} ${prevY}).`);
              return;
          }

          const formattedData: Person[] = data.map((d: any) => {
              const padded: Shift[] = Array(31).fill('L');
              if (d.shifts && Array.isArray(d.shifts)) {
                  d.shifts.forEach((s: Shift, idx: number) => {
                      if (idx < 31) padded[idx] = s;
                  });
              }
              return {
                  ...d,
                  shifts: padded,
                  external_shifts: d.external_shifts || Array(31).fill(null),
              };
          });

          setPeople(formattedData);

          const hasNonL = data.some((d: any) => d.shifts && d.shifts.some((s: string) => s !== 'L'));
          if (hasNonL) {
              setAlertMsg(`Se copiaron correctamente ${formattedData.length} funcionario(s) y sus turnos desde ${prevMonthName} ${prevY}.`);
          } else {
              setAlertMsg(`Se cargó el personal del mes anterior (${prevMonthName} ${prevY}), pero todos sus turnos están en Libre.`);
          }
      } catch (e) {
          setAlertMsg('Error al consultar los turnos del mes anterior.');
      }
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
      // 1. Pre-validar conflictos con turnos externos en otras áreas
      for (const p of people) {
          if (!p.external_shifts) continue;
          for (let dayIdx = 0; dayIdx < daysInMonth; dayIdx++) {
              const s = p.shifts[dayIdx] || 'L';
              const ext = p.external_shifts[dayIdx];
              if (s !== 'L' && ext && shiftsOverlap(s, ext.code as Shift)) {
                  setAlertMsg(`Conflicto de horario: ${p.name} tiene turno ${s} el día ${dayIdx + 1}, pero ya tiene programado el turno ${ext.code} (${ext.name}) en el área "${ext.dept}". Debes cambiarlo a Libre (L) o a un turno sin superposición antes de guardar.`);
                  return;
              }
          }
      }

      // 2. Pre-validar conflictos internos entre filas del mismo funcionario en áreas distintas
      for (let i = 0; i < people.length; i++) {
          for (let j = i + 1; j < people.length; j++) {
              const p1 = people[i];
              const p2 = people[j];
              if (p1.id === p2.id && p1.dept_id !== p2.dept_id) {
                  for (let dayIdx = 0; dayIdx < daysInMonth; dayIdx++) {
                      const s1 = p1.shifts[dayIdx] || 'L';
                      const s2 = p2.shifts[dayIdx] || 'L';
                      if (s1 !== 'L' && s2 !== 'L' && shiftsOverlap(s1, s2)) {
                          setAlertMsg(`Conflicto de horario: ${p1.name} no puede tener turnos en el mismo horario el día ${dayIdx + 1} entre "${p1.dept_name || 'Área 1'}" (${s1}) y "${p2.dept_name || 'Área 2'}" (${s2}).`);
                          return;
                      }
                  }
              }
          }
      }

      const activeServiceId = currentServiceId || role.serviceId;

      try {
          const res = await fetch(getApiUrl('/api/roles/save'), {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                  year,
                  month,
                  serviceId: activeServiceId,
                  people
              })
          });
          if (res.ok) {
              setRole({ year, month, service, serviceId: activeServiceId }); 
              setView('report');
          } else {
              const data = await res.json();
              setAlertMsg(data.error || 'Error al guardar el rol');
          }
      } catch (e) {
          setAlertMsg('Error de red al intentar guardar');
      }
  }
  
  const sortedPeople = useMemo(() => {
      return [...people].sort((a, b) => {
          const deptA = a.dept_name || '';
          const deptB = b.dept_name || '';
          if (deptA !== deptB) return deptA.localeCompare(deptB);

          const rankA = a.is_jefe ? 2 : 0;
          const rankB = b.is_jefe ? 2 : 0;
          if (rankA !== rankB) return rankB - rankA;
          return a.name.localeCompare(b.name);
      });
  }, [people]);

  return <main className="content editor-page"><div className="page-heading"><div><span className="eyebrow">NUEVO REGISTRO</span><h1>Registrar rol mensual</h1><p>Completa la matriz de turnos para generar la planilla oficial.</p></div><button className="secondary-button" onClick={() => setView('dashboard')}><ArrowLeft size={16} /> Volver al resumen</button></div><section className="form-card"><div className="form-card-heading"><div><h2>Datos del período</h2><p>Define el período y servicio al que corresponde este rol.</p></div><span className="step-label">PASO 1 DE 2</span></div><div className="period-form"><label>Año<select value={year} onChange={(event) => handleYearChange(event.target.value)}><option>2026</option><option>2027</option><option>2028</option></select></label><label>Mes<select value={month} onChange={(event) => handleMonthChange(event.target.value)}>{monthNames.map((name, idx) => <option key={name} value={name}>{String(idx + 1).padStart(2, '0')} - {name}</option>)}</select></label><label className="service-field">Departamento / Servicio<select value={service} onChange={onServiceChange} disabled={userObj.rol !== 'Administrador'} >
      {userObj.rol === 'Administrador' && <option value="Todos los departamentos">Todos los departamentos</option>}
      {depts.map(d => <option key={d.id_departamento} value={d.nombre_departmento}>{d.nombre_departmento}</option>)}
      {depts.length === 0 && <option>{service}</option>}
  </select></label></div></section><section className="form-card matrix-card"><div className="form-card-heading"><div><h2>Matriz de captura masiva</h2><p>Selecciona el turno correspondiente en cada día para cada funcionario.</p></div><span className="step-label">PASO 2 DE 2</span></div><div style={{ display: 'flex', gap: '10px', alignItems: 'center', justifyContent: 'space-between', marginBottom: '15px', flexWrap: 'wrap' }}><div className="shift-legend" style={{ margin: 0 }}><span><i className="shift-m" /> M · Mañana</span><span><i className="shift-mstar" /> M* · Extendido</span><span><i className="shift-t" /> T · Tarde</span><span><i className="shift-n" /> N · Noche</span><span><i className="shift-l" /> L · Libre</span><span style={{ display: 'inline-flex', alignItems: 'center', gap: '5px', color: '#b45309', fontWeight: '500', fontSize: '11px', background: '#fef3c7', padding: '2px 8px', borderRadius: '6px', border: '1px solid #fde68a' }}><i style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#f59e0b', display: 'inline-block' }} /> Ocupado en otra área</span></div>{userObj.rol !== 'Administrador' && (<div style={{ display: 'flex', gap: '8px', alignItems: 'center', flexWrap: 'wrap' }}>{(ownStaff.length > 0 || sharedStaff.length > 0) && (<select value="" onChange={(e) => { if (e.target.value) addEmployee(Number(e.target.value)); }} style={{ padding: '6px 12px', borderRadius: '8px', border: '1px solid #cbd9e1', background: '#fff', fontSize: '12px', cursor: 'pointer', outline: 'none' }}><option value="">+ Agregar funcionario al rol...</option>{ownStaff.length > 0 && (<optgroup label="Personal de esta área">{ownStaff.map(e => (<option key={e.id_empleado} value={e.id_empleado}>+ {e.nombre_completo} ({e.cargo ? e.cargo.nombre_cargo : 'Personal'})</option>))}</optgroup>)}{sharedStaff.length > 0 && (<optgroup label="Personal de otras áreas (Compartido)">{sharedStaff.map(e => (<option key={e.id_empleado} value={e.id_empleado}>+ {e.nombre_completo} ({e.departamento ? e.departamento.nombre_departmento : 'Sin área'})</option>))}</optgroup>)}</select>)}{ownStaff.length > 0 && (<button type="button" className="secondary-button" onClick={addAllDepartmentStaff} style={{ fontSize: '11px', padding: '6px 10px' }} title="Agrega todos los empleados de esta área a la planilla"><Users size={13} style={{ marginRight: '4px' }} /> Agregar todo el personal ({ownStaff.length})</button>)}</div>)}</div><div className="editor-table-wrap" style={{ maxHeight: '600px', overflowY: 'auto' }}><table className="editor-table"><thead><tr><th className="sticky-col first" style={{ zIndex: 4 }}>N°</th><th className="sticky-col person-col" style={{ zIndex: 4 }}>Personal</th>{days.map((day) => <th key={day}>{day}</th>)}<th>Total Hrs</th></tr></thead><tbody>{sortedPeople.map((person, index) => {
    const isJefe = person.is_jefe;
    return (
        <tr key={person.bio} style={{ background: isJefe ? '#f8fafc' : undefined }}>
            <td className="sticky-col first" style={{ zIndex: 3, fontWeight: isJefe ? 'bold' : 'normal' }}>{index + 1}</td>
            <td className="sticky-col person-col" style={{ borderLeft: isJefe ? '4px solid #0284c7' : 'none', paddingLeft: isJefe ? '8px' : '12px', background: isJefe ? '#f1f5f9' : undefined }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                    <strong>{person.name}</strong>
                    {isJefe && (
                        <span style={{ fontSize: '9px', background: '#eff6ff', color: '#1d4ed8', padding: '1px 5px', borderRadius: '4px', fontWeight: 'bold', border: '1px solid #bfdbfe', textTransform: 'uppercase', letterSpacing: '0.3px' }}>
                            JEFE
                        </span>
                    )}
                </div>
                <span style={{ fontSize: '11px', color: '#64748b' }}>
                    {isJefe || person.role?.toLowerCase().includes('jefe')
                        ? `${person.role}: ${person.dept_name || 'Sin Área'}`
                        : person.role}
                </span>
                <div style={{ display: 'flex', gap: '4px', marginTop: '5px', flexWrap: 'wrap' }}>
                    <button type="button" onClick={() => fillRow(person.id, 0)} style={{ fontSize: '10px', padding: '2px 5px', cursor: 'pointer', border: '1px solid #d1d5db', borderRadius: '4px', background: '#f3f4f6', color: '#374151' }} title="Copia el turno del día 1 a todo el mes">Repetir Día 1</button>
                    {!isJefe && (
                        <button type="button" onClick={() => removePerson(person.id)} style={{ fontSize: '10px', padding: '2px 5px', cursor: 'pointer', border: '1px solid #fecaca', borderRadius: '4px', background: '#fef2f2', color: '#ef4444' }} title="Quitar funcionario de este rol">Quitar</button>
                    )}
                </div>
            </td>
            {days.map((day, dayIndex) => {
                const ext = person.external_shifts ? person.external_shifts[dayIndex] : null;
                const hasExt = !!ext;
                return (
                    <td
                        key={`${person.bio}-${dayIndex}`}
                        onDoubleClick={() => fillRow(person.id, dayIndex)}
                        title={hasExt ? `Ocupado en ${ext.dept}: Turno ${ext.code} (${ext.name})` : "Doble clic para repetir este turno hasta fin de mes"}
                        style={{ position: 'relative' }}
                    >
                        {hasExt && (
                            <span
                                title={`Ocupado en ${ext.dept}: Turno ${ext.code} (${ext.name})`}
                                style={{
                                    position: 'absolute',
                                    top: '3px',
                                    right: '3px',
                                    width: '6px',
                                    height: '6px',
                                    borderRadius: '50%',
                                    backgroundColor: '#f59e0b',
                                    boxShadow: '0 0 2px rgba(0,0,0,0.4)',
                                    zIndex: 2,
                                    pointerEvents: 'none',
                                }}
                            />
                        )}
                        <select
                            aria-label={`${person.name}, día ${dayIndex + 1}`}
                            value={person.shifts[dayIndex] || 'L'}
                            onChange={(event) => updateShift(person.id, dayIndex, event.target.value as Shift)}
                            className={`shift-select shift-${(person.shifts[dayIndex] || 'L').replace('*', 'star').toLowerCase()}`}
                        >
                            <option value="M" disabled={hasExt && shiftsOverlap('M', ext.code as Shift)}>
                                M {hasExt && shiftsOverlap('M', ext.code as Shift) ? `(${ext.dept})` : ''}
                            </option>
                            <option value="M*" disabled={hasExt && shiftsOverlap('M*', ext.code as Shift)}>
                                M* {hasExt && shiftsOverlap('M*', ext.code as Shift) ? `(${ext.dept})` : ''}
                            </option>
                            <option value="T" disabled={hasExt && shiftsOverlap('T', ext.code as Shift)}>
                                T {hasExt && shiftsOverlap('T', ext.code as Shift) ? `(${ext.dept})` : ''}
                            </option>
                            <option value="N" disabled={hasExt && shiftsOverlap('N', ext.code as Shift)}>
                                N {hasExt && shiftsOverlap('N', ext.code as Shift) ? `(${ext.dept})` : ''}
                            </option>
                            <option value="L">L</option>
                        </select>
                    </td>
                );
            })}
            <td style={{ fontWeight: 'bold' }}>{calcHours(person.shifts.slice(0, daysInMonth))}h</td>
        </tr>
    );
})}</tbody></table></div><div className="editor-actions"><span style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start', gap: '4px' }}><div><ShieldCheck size={15} style={{ verticalAlign: 'middle', marginRight: '5px' }} /> Los cambios se guardan al procesar el rol.</div><div style={{ fontSize: '11px', color: '#637582' }}><b>Consejo:</b> Doble clic en cualquier turno para repetirlo hasta fin de mes.</div></span><div style={{ display: 'flex', gap: '10px', alignItems: 'center', flexWrap: 'wrap' }}><button type="button" className="secondary-button" onClick={loadPreviousMonth} title="Copia el personal y los turnos del mes anterior"><Copy size={15} style={{ marginRight: '5px' }} /> Copiar del mes anterior</button><button type="button" className="secondary-button" onClick={resetToOnlyJefe} title="Reinicia la planilla: deja solo al Jefe con turnos en Libre"><RotateCcw size={15} style={{ marginRight: '5px' }} /> Comenzar desde cero (Solo Jefe)</button><button className="primary-button" onClick={save}><Save size={16} /> Guardar y procesar rol</button></div></div></section>
  {alertMsg && (
      <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
          <div style={{ background: '#fff', padding: '30px', borderRadius: '16px', width: '100%', maxWidth: '400px', boxShadow: '0 10px 25px rgba(0,0,0,0.2)' }}>
              <h3 style={{ marginTop: 0, marginBottom: '15px', fontSize: '18px', color: 'var(--navy)' }}>Atención</h3>
              <p style={{ color: 'var(--muted)', fontSize: '14px', marginBottom: '25px', lineHeight: 1.5 }}>
                  {alertMsg}
              </p>
              <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                  <button onClick={() => setAlertMsg('')} className="primary-button" style={{ padding: '10px 20px' }}>
                      Entendido
                  </button>
              </div>
          </div>
      </div>
  )}
  </main>
}

function Reports({ setView, people, role, setRole, userObj }: { setView: (view: View) => void; people: Person[]; role: RoleMeta; setRole: React.Dispatch<React.SetStateAction<RoleMeta>>; userObj: User }) {
  const [selectedYear, setSelectedYear] = useState(role.year);
  const [selectedMonth, setSelectedMonth] = useState(role.month);
  const [depts, setDepts] = useState<any[]>([]);

  useEffect(() => {
      setSelectedYear(role.year);
      setSelectedMonth(role.month);
  }, [role.year, role.month]);

  useEffect(() => {
      fetch(getApiUrl('/api/departamentos')).then(res=>res.json()).then(data=>setDepts(data));
  }, []);

  const onServiceChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
      if (e.target.value === 'Todos los departamentos') {
          setRole(r => ({...r, serviceId: 'todos', service: 'Todos los departamentos'}))
      } else {
          const d = depts.find(x => x.nombre_departmento === e.target.value)
          if(d) {
              setRole(r => ({...r, serviceId: String(d.id_departamento), service: d.nombre_departmento}))
          }
      }
  }

  const handleView = () => {
      setRole({ ...role, year: selectedYear, month: selectedMonth });
      setView('report');
  }

  return <main className="content"><div className="page-heading"><div><span className="eyebrow">ARCHIVO DE ROLES</span><h1>Roles existentes</h1><p>Consulta los registros mensuales y genera una copia para impresión.</p></div>
  {userObj.rol !== 'Operador' && (
      <button className="primary-button" onClick={() => setView('editor')}><CalendarDays size={16} /> Registrar nuevo rol</button>
  )}
  </div><section className="report-list-card"><div className="list-toolbar" style={{ display: 'flex', gap: '15px' }}>
      <select value={selectedYear} onChange={(e) => { setSelectedYear(e.target.value); setRole(r => ({ ...r, year: e.target.value })); }} className="search-placeholder" style={{ width: '120px' }}>
          <option>2026</option><option>2027</option><option>2028</option>
      </select>
      <select value={selectedMonth} onChange={(e) => { setSelectedMonth(e.target.value); setRole(r => ({ ...r, month: e.target.value })); }} className="search-placeholder" style={{ width: '180px' }}>
          {monthNames.map((m, idx) => <option key={m} value={m}>{String(idx + 1).padStart(2, '0')} - {m}</option>)}
      </select>
      {userObj.rol === 'Administrador' ? (
          <select value={role.service} onChange={onServiceChange} className="search-placeholder" style={{ flex: 1 }}>
              <option value="Todos los departamentos">Todos los departamentos</option>
              {depts.map(d => <option key={d.id_departamento} value={d.nombre_departmento}>{d.nombre_departmento}</option>)}
          </select>
      ) : (
          <div className="search-placeholder" style={{ opacity: 0.7 }}>{role.service}</div>
      )}
  </div><div className="report-row"><div className="report-icon"><ClipboardList size={20} /></div><div className="report-info"><strong>{role.service}</strong><span>Rol mensual · {selectedMonth} {selectedYear}</span></div><span className="badge green-badge">Visualizar</span><button className="icon-button" onClick={handleView} aria-label="Ver rol"><Printer size={17} /></button></div></section></main>
}

function HistoryView({ setView, role, setRole, userObj }: { setView: (view: View) => void; role: RoleMeta; setRole: React.Dispatch<React.SetStateAction<RoleMeta>>; userObj: User }) {
  const [history, setHistory] = useState<any[]>([]);
  const [filterDept, setFilterDept] = useState('Todos');
  const [filterMonth, setFilterMonth] = useState('Todos');
  const [filterYear, setFilterYear] = useState('Todos');

  useEffect(() => {
      let qs = '';
      if (userObj.rol !== 'Administrador' && userObj.id_departamento) {
          qs = `?departamento_id=${userObj.id_departamento}`;
      } else {
          qs = '?departamento_id=todos';
      }
      fetch(getApiUrl('/api/roles/history') + qs)
          .then(res => res.json())
          .then(data => setHistory(data));
  }, [userObj]);

  const handleView = (item: any) => {
      setRole({ 
          year: String(item.anio), 
          month: item.mesNombre, 
          service: item.is_consolidado ? 'Todos los departamentos' : item.departamento_nombre, 
          serviceId: item.is_consolidado ? 'todos' : String(item.departamento_id) 
      });
      setView('report');
  }

  const uniqueDepts = Array.from(new Set(history.map(h => h.departamento_nombre))).sort();
  const uniqueYears = Array.from(new Set(history.map(h => String(h.anio)))).sort((a,b) => b.localeCompare(a));

  const filteredHistory = history.filter(item => {
      let matchDept = false;
      if (filterDept === 'Todos') {
          matchDept = true;
      } else if (filterDept === 'Administración (Global)' || filterDept.includes('Administración') || filterDept.includes('Global')) {
          matchDept = item.is_consolidado || item.departamento_nombre.includes('Administración') || item.departamento_nombre.includes('Global');
      } else {
          matchDept = item.departamento_nombre === filterDept;
      }

      const matchMonth = filterMonth === 'Todos' || item.mesNombre === filterMonth;
      const matchYear = filterYear === 'Todos' || String(item.anio) === filterYear;
      return matchDept && matchMonth && matchYear;
  });

  const targetYear = filterYear !== 'Todos' 
      ? filterYear 
      : (filteredHistory[0]?.anio ? String(filteredHistory[0].anio) : role.year);
  const canPrintConsolidated = userObj.rol === 'Administrador' && filterMonth !== 'Todos' && (filterDept === 'Todos' || filterDept.includes('Global') || filterDept.includes('Administración'));

  return <main className="content"><div className="page-heading"><div><span className="eyebrow">RESPALDO</span><h1>Historia de roles guardados</h1><p>Consulta los registros mensuales guardados exitosamente en la base de datos.</p></div></div>
  <section className="report-list-card" style={{ padding: '20px' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: '15px', marginBottom: '20px', flexWrap: 'wrap' }}>
          <div style={{ display: 'flex', gap: '10px', flex: 1, minWidth: '300px' }}>
              {userObj.rol === 'Administrador' && (
                  <select value={filterDept} onChange={e => setFilterDept(e.target.value)} style={{ flex: 2, padding: '10px', borderRadius: '8px', border: '1px solid #cbd9e1', fontSize: '13px', outline: 'none' }}>
                      <option value="Todos">Todos los departamentos</option>
                      {uniqueDepts.map(d => <option key={d as string} value={d as string}>{d as string}</option>)}
                  </select>
              )}
              <select value={filterMonth} onChange={e => setFilterMonth(e.target.value)} style={{ flex: 1, padding: '10px', borderRadius: '8px', border: '1px solid #cbd9e1', fontSize: '13px', outline: 'none' }}>
                  <option value="Todos">Cualquier Mes</option>
                  {monthNames.map((m, idx) => <option key={m} value={m}>{String(idx + 1).padStart(2, '0')} - {m}</option>)}
              </select>
              <select value={filterYear} onChange={e => setFilterYear(e.target.value)} style={{ flex: 1, padding: '10px', borderRadius: '8px', border: '1px solid #cbd9e1', fontSize: '13px', outline: 'none' }}>
                  <option value="Todos">Cualquier Año</option>
                  {uniqueYears.map(y => <option key={y as string} value={y as string}>{y as string}</option>)}
              </select>
          </div>
          <div style={{ color: '#637582', fontSize: '13px', padding: '10px 15px', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
              <strong>{filteredHistory.length}</strong> resultados
          </div>
      </div>

      {canPrintConsolidated && (
          <div style={{ padding: '15px 20px', marginBottom: '15px', background: '#f0f9fc', border: '1px solid #afd3e1', borderRadius: '8px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                  <strong style={{ color: 'var(--navy)', display: 'block', fontSize: '14px', marginBottom: '4px' }}>Reporte Consolidado (Todas las Áreas)</strong>
                  <span style={{ color: 'var(--muted)', fontSize: '12px' }}>Imprime la planilla de todos los empleados del hospital para {filterMonth} {targetYear}.</span>
              </div>
              <button className="primary-button" onClick={() => { 
                  setRole({ year: targetYear, month: filterMonth, service: 'Todos los departamentos', serviceId: 'todos' }); 
                  setView('report'); 
              }}>
                  <Printer size={16} /> Imprimir Consolidado
              </button>
          </div>
      )}

      {filteredHistory.map((item, index) => {
          const isConsolidated = item.is_consolidado;
          return (
              <div key={item.id} className="report-row" style={{ borderTop: index === 0 ? 'none' : '1px solid var(--line)', padding: '18px 0', background: isConsolidated ? '#f8fafc' : undefined, borderRadius: isConsolidated ? '8px' : undefined, margin: isConsolidated ? '6px 0' : undefined }}>
                  <div className="report-icon" style={{ color: isConsolidated ? '#0284c7' : undefined }}>
                      <Archive size={20} />
                  </div>
                  <div className="report-info">
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                          <strong>{item.departamento_nombre}</strong>
                          {isConsolidated && (
                              <span style={{ fontSize: '10px', background: '#e0f2fe', color: '#0369a1', padding: '1px 6px', borderRadius: '4px', fontWeight: 'bold' }}>
                                  CONSOLIDADO HOSPITAL
                              </span>
                          )}
                      </div>
                      <span>Rol mensual · {item.mesNombre} {item.anio}</span>
                  </div>
                  <span className={`badge ${isConsolidated ? 'blue-badge' : 'green-badge'}`}>
                      {isConsolidated ? 'Consolidado' : 'Guardado'}
                  </span>
                  <button className="icon-button" onClick={() => handleView(item)} aria-label="Ver rol" title="Abrir y visualizar este rol">
                      <Printer size={17} />
                  </button>
              </div>
          );
      })}
      {filteredHistory.length === 0 && <p style={{ color: '#888', fontSize: '13px' }}>{history.length === 0 ? 'Aún no hay roles guardados en el sistema.' : 'No se encontraron resultados para la búsqueda.'}</p>}
  </section></main>
}

function Report({ setView, people, role }: { setView: (view: View) => void; people: Person[]; role: RoleMeta }) {
  const daysInMonth = new Date(parseInt(role.year), monthNames.indexOf(role.month) + 1, 0).getDate();
  const dynamicDays = Array.from({ length: daysInMonth }, (_, i) => i + 1);

  const groupedPeople = useMemo(() => {
      const groups: { [key: string]: { deptName: string; members: Person[] } } = {};
      people.forEach((person) => {
          const key = person.dept_name || 'Personal Sin Área Asignada';
          if (!groups[key]) {
              groups[key] = {
                  deptName: key,
                  members: []
              };
          }
          groups[key].members.push(person);
      });
      return Object.values(groups).map(g => ({
          ...g,
          members: [...g.members].sort((a, b) => {
              const rankA = a.is_jefe ? 2 : 0;
              const rankB = b.is_jefe ? 2 : 0;
              if (rankA !== rankB) return rankB - rankA;
              return a.name.localeCompare(b.name);
          })
      }));
  }, [people]);

  return <main className="report-page"><div className="report-toolbar"><button className="secondary-button" onClick={() => setView('reports')}><ArrowLeft size={16} /> Volver a roles</button><button className="primary-button" onClick={() => window.print()}><Printer size={16} /> Imprimir rol</button></div><article className="print-sheet"><header className="roster-header"><div className="institution-block"><div className="hospital-name">HOSPITAL JAPONÉS</div><div className="received-box">SELLO DE RECEPCIÓN<br /><span>FECHA: ____ / ____ / ______</span></div></div><div className="title-block"><h1>ROL DE TURNO MENSUAL</h1><p>{role.service.toUpperCase()}</p></div><div className="period-box"><span>PERÍODO</span><strong>MES: {role.month.toUpperCase()}</strong><strong>AÑO: {role.year}</strong></div></header><section className="legend-row"><div className="legend-items"><b>LEYENDA:</b><span><strong>M</strong> = Mañana 07:30 a 13:30</span><span><strong>M*</strong> = Mañana extendido</span><span><strong>T</strong> = Tarde</span><span><strong>N</strong> = Noche</span><span><strong>L</strong> = Libre</span></div></section><div className="table-wrap"><table className="roster-table"><caption className="sr-only">Rol de turno mensual</caption><colgroup><col className="col-no" /><col className="col-ff" /><col className="col-item" /><col className="col-bio" /><col className="col-phone" /><col className="col-name" /><col className="col-role" />{dynamicDays.map((day) => <col key={day} className="col-day" />)}{[1, 2, 3, 4, 5].map((item) => <col key={item} className="col-total" />)}</colgroup><thead><tr className="group-row"><th colSpan={7}>DATOS DEL PERSONAL</th><th colSpan={daysInMonth}>DÍAS DEL MES — {role.month.toUpperCase()} {role.year}</th><th colSpan={5}>TOTALES</th></tr><tr className="header-row"><th>N°</th><th>F.<br />FIN.</th><th>ITEM</th><th>COD.<br />BIOMÉTRICO</th><th>TELF.</th><th>NOMBRE</th><th>CARGO</th>{dynamicDays.map((day) => <th key={day}>{day}</th>)}<th>MAÑANA</th><th>TARDE</th><th>NOCHE</th><th>LIBRE</th><th>TOTAL</th></tr></thead><tbody>{groupedPeople.map((group) => (
      <Fragment key={`rep-group-${group.deptName}`}>
          {groupedPeople.length > 1 && (
              <tr style={{ background: '#f1f5f9', fontWeight: 'bold' }}>
                  <td colSpan={7 + daysInMonth + 5} style={{ textAlign: 'left', padding: '6px 10px', fontSize: '11px', textTransform: 'uppercase' }}>
                      ÁREA / SERVICIO: {group.deptName} ({group.members.length} {group.members.length === 1 ? 'FUNCIONARIO' : 'FUNCIONARIOS'})
                  </td>
              </tr>
          )}
          {group.members.map((person) => {
              const globalIdx = people.findIndex(p => p.id === person.id);
              const totals = getTotals(person.shifts.slice(0, daysInMonth));
              return (
                  <tr key={person.bio} style={{ background: person.is_jefe ? '#fafafa' : undefined }}>
                      <td>{globalIdx + 1}</td>
                      <td>{person.ff}</td>
                      <td>{person.item}</td>
                      <td>{person.bio}</td>
                      <td>{person.phone}</td>
                      <td className="name-cell">
                          <strong>{person.name}</strong>
                          {person.is_jefe && <small style={{ color: '#b45309', fontWeight: 'bold' }}> (JEFE)</small>}
                      </td>
                      <td className="role-cell">
                          {person.is_jefe || person.role?.toLowerCase().includes('jefe')
                              ? `${person.role}: ${person.dept_name || 'Sin Área'}`
                              : person.role}
                      </td>
                      {person.shifts.slice(0, daysInMonth).map((shift, index) => (
                          <td className={`shift-cell ${shift === 'L' ? 'free' : ''}`} key={`${person.bio}-${index}`}>
                              {shift === 'L' ? '' : shift}
                          </td>
                      ))}
                      <td className="total-cell">{totals.M}</td>
                      <td className="total-cell">{totals.T}</td>
                      <td className="total-cell">{totals.N}</td>
                      <td className="total-cell">{totals.L}</td>
                      <td className="total-cell">{daysInMonth}</td>
                  </tr>
              );
          })}
      </Fragment>
  ))}</tbody><tfoot><tr><td colSpan={7}>TOTAL PERSONAL PROGRAMADO: {people.length}</td><td colSpan={daysInMonth}></td><td colSpan={5}></td></tr></tfoot></table></div><footer className="signatures"><div>ELABORADO POR:<span /></div><div>REVISADO POR:<span /></div><div>APROBADO POR:<span /></div></footer></article></main>
}

function AssignView({ setView, userObj, role, setRole }: { setView: (view: View) => void; userObj: User; role: RoleMeta; setRole: React.Dispatch<React.SetStateAction<RoleMeta>> }) {
  const [unassigned, setUnassigned] = useState<any[]>([]);
  const [depts, setDepts] = useState<any[]>([]);
  const [selectedDept, setSelectedDept] = useState<string>(userObj.id_departamento ? String(userObj.id_departamento) : '');
  const [search, setSearch] = useState('');
  const [filterDept, setFilterDept] = useState('Todos');
  const [initialFilterSet, setInitialFilterSet] = useState(false);
  const [year, setYear] = useState(role.year);
  const [month, setMonth] = useState(role.month);

  const [modalOpen, setModalOpen] = useState(false);
  const [targetEmp, setTargetEmp] = useState<any>(null);
  const [targetDept, setTargetDept] = useState<string>('');
  
  const [alertMsg, setAlertMsg] = useState('');
  const [removeModalOpen, setRemoveModalOpen] = useState(false);
  const [empToRemove, setEmpToRemove] = useState<number | null>(null);
  const [deptToRemove, setDeptToRemove] = useState<number | null>(null);

  useEffect(() => {
      setYear(role.year);
      setMonth(role.month);
  }, [role.year, role.month]);

  const load = (m = role.month, y = role.year) => {
      const mNum = monthNames.indexOf(m) + 1;
      fetch(getApiUrl(`/api/empleados/unassigned?mes=${mNum}&anio=${y}`)).then(r=>r.json()).then(data => setUnassigned(data));
      fetch(getApiUrl('/api/departamentos')).then(r=>r.json()).then(data => {
          setDepts(data);
          setInitialFilterSet(prev => {
              if (!prev && userObj.rol !== 'Administrador' && userObj.id_departamento) {
                  const myDept = data.find((d: any) => String(d.id_departamento) === String(userObj.id_departamento));
                  if (myDept) {
                      setFilterDept(myDept.nombre_departmento);
                  }
              }
              return true;
          });
      });
  }

  useEffect(() => { load(role.month, role.year); }, [role.month, role.year]);

  const openAssignModal = (emp: any) => {
      setTargetEmp(emp);
      if (userObj.rol === 'Administrador') {
          const currentFilterDept = depts.find(d => d.nombre_departmento === filterDept);
          if (currentFilterDept) {
              setTargetDept(String(currentFilterDept.id_departamento));
          } else {
              setTargetDept(depts.length > 0 ? String(depts[0].id_departamento) : '');
          }
      } else {
          setTargetDept(String(userObj.id_departamento));
      }
      setModalOpen(true);
  }

  const confirmAssign = async () => {
      if (!targetDept) { setAlertMsg('Seleccione un departamento destino primero.'); return; }
      const mNum = monthNames.indexOf(role.month) + 1;
      const res = await fetch(getApiUrl('/api/empleados/assign'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
              employee_id: targetEmp.id, 
              department_id: targetDept,
              mes: mNum,
              anio: role.year
          })
      });
      if (res.ok) {
          setModalOpen(false);
          setTargetEmp(null);
          load(role.month, role.year);
      } else {
          setAlertMsg('Error al asignar el empleado.');
      }
  }

  const confirmRemove = (empId: number, deptId: number | null = null) => {
      setEmpToRemove(empId);
      setDeptToRemove(deptId);
      setRemoveModalOpen(true);
  }

  const executeRemove = async () => {
      if (empToRemove === null) return;
      const mNum = monthNames.indexOf(role.month) + 1;
      const res = await fetch(getApiUrl('/api/empleados/remove'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
              employee_id: empToRemove,
              department_id: deptToRemove || (userObj.rol !== 'Administrador' ? userObj.id_departamento : null),
              mes: mNum,
              anio: role.year
          })
      });
      if (res.ok) {
          load(role.month, role.year);
      } else {
          setAlertMsg('Error al remover el empleado.');
      }
      setRemoveModalOpen(false);
      setEmpToRemove(null);
      setDeptToRemove(null);
  }

  const copyPreviousMonthAssignments = async () => {
      const mNum = monthNames.indexOf(role.month) + 1;
      let targetDeptParam: any = userObj.rol === 'Administrador' ? 'todos' : userObj.id_departamento;
      if (userObj.rol === 'Administrador') {
          const deptObj = depts.find(d => d.nombre_departmento === filterDept);
          if (deptObj) {
              targetDeptParam = deptObj.id_departamento;
          }
      }
      const res = await fetch(getApiUrl('/api/empleados/copy-month'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
              mes: mNum,
              anio: role.year,
              department_id: targetDeptParam
          })
      });
      const data = await res.json();
      if (res.ok) {
          setAlertMsg(`Se copiaron ${data.count} asignaciones desde ${data.prev_mes_nombre} ${data.prev_anio}.`);
          load(role.month, role.year);
      } else {
          setAlertMsg(data.error || 'No se pudieron copiar las asignaciones del mes anterior.');
      }
  }

  const filteredEmployees = unassigned.filter(emp => {
      const matchSearch = search.trim() === '' || 
          emp.name.toLowerCase().includes(search.toLowerCase()) || 
          emp.bio.toLowerCase().includes(search.toLowerCase());
      
      let matchesDept = true;

      if (filterDept === 'Todos') {
          matchesDept = true;
      } else if (filterDept === 'Sin Área') {
          const hasNoAssigned = !emp.assigned_dept_ids || emp.assigned_dept_ids.length === 0;
          const hasNoBase = !emp.base_dept_id || emp.base_dept_name === 'Sin Área';
          matchesDept = hasNoAssigned && hasNoBase;
      } else {
          const deptObj = depts.find(d => d.nombre_departmento.toLowerCase() === filterDept.toLowerCase());
          const targetDeptId = deptObj ? Number(deptObj.id_departamento) : null;

          const isInAssignedMonth = targetDeptId && emp.assigned_dept_ids 
              ? emp.assigned_dept_ids.includes(targetDeptId) 
              : false;

          const isBaseDept = (targetDeptId && emp.base_dept_id && Number(emp.base_dept_id) === targetDeptId) ||
              (emp.base_dept_name && emp.base_dept_name.toLowerCase() === filterDept.toLowerCase()) ||
              (targetDeptId && emp.dept_id && Number(emp.dept_id) === targetDeptId);

          matchesDept = isInAssignedMonth || isBaseDept;
      }

      return matchSearch && matchesDept;
  });

  return (
    <main className="content">
      <div className="page-heading">
        <div>
          <span className="eyebrow">RECURSOS HUMANOS</span>
          <h1>Asignación de Personal</h1>
          <p>Asigna o incorpora personal a tu servicio para el mes seleccionado. Recuerda que un funcionario puede colaborar con más de un área o jefe siempre y cuando sus turnos no choquen.</p>
        </div>
      </div>

      <section className="report-list-card" style={{ padding: '25px', background: '#fff' }}>
          
          <div style={{ display: 'flex', gap: '15px', alignItems: 'center', marginBottom: '20px', flexWrap: 'wrap', background: '#f8fafc', padding: '15px 20px', borderRadius: '12px', border: '1px solid #e2e8f0' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <CalendarDays size={18} color="#0284c7" />
                  <strong style={{ fontSize: '13px', color: 'var(--navy)' }}>Período de Asignación:</strong>
              </div>
              <select value={role.year} onChange={(e) => { const newY = e.target.value; setYear(newY); setRole(r => ({ ...r, year: newY })); }} style={{ padding: '8px 12px', borderRadius: '8px', border: '1px solid #cbd9e1', background: '#fff', fontSize: '13px', outline: 'none' }}>
                  <option>2026</option>
                  <option>2027</option>
                  <option>2028</option>
              </select>
              <select value={role.month} onChange={(e) => { const newM = e.target.value; setMonth(newM); setRole(r => ({ ...r, month: newM })); }} style={{ padding: '8px 12px', borderRadius: '8px', border: '1px solid #cbd9e1', background: '#fff', fontSize: '13px', outline: 'none' }}>
                  {monthNames.map((m, idx) => (
                      <option key={m} value={m}>
                          {String(idx + 1).padStart(2, '0')} - {m}
                      </option>
                  ))}
              </select>
              <button type="button" className="secondary-button" onClick={copyPreviousMonthAssignments} style={{ fontSize: '12px', padding: '8px 14px', marginLeft: 'auto' }} title="Copia el personal del mes anterior hacia este mes">
                  <Copy size={14} style={{ marginRight: '5px' }} /> Copiar asignaciones del mes anterior
              </button>
          </div>
          
          <div style={{ display: 'flex', gap: '20px', marginBottom: '25px' }}>
              <div style={{ flex: 1, position: 'relative' }}>
                  <Search size={18} style={{ position: 'absolute', left: '15px', top: '13px', color: '#64748b' }} />
                  <input type="text" placeholder="Buscar por nombre o código biométrico..." value={search} onChange={e => setSearch(e.target.value)} style={{ width: '100%', padding: '12px 15px 12px 45px', borderRadius: '10px', border: '1px solid #cbd9e1', background: '#f8fafc', outline: 'none', transition: 'box-shadow 0.2s', fontSize: '13px' }} onFocus={(e) => e.target.style.boxShadow = '0 0 0 3px rgba(0,166,166,0.15)'} onBlur={(e) => e.target.style.boxShadow = 'none'} />
              </div>
              <div style={{ width: '280px' }}>
                  <select value={filterDept} onChange={e => setFilterDept(e.target.value)} style={{ width: '100%', padding: '12px 15px', borderRadius: '10px', border: '1px solid #cbd9e1', background: '#f8fafc', outline: 'none', fontSize: '13px' }}>
                      <option value="Todos">Filtro: Todas las Áreas (Hospital)</option>
                      <option value="Sin Área">Filtro: Empleados Sin Área</option>
                      {depts.map(d => <option key={d.id_departamento} value={d.nombre_departmento}>{d.nombre_departmento}</option>)}
                  </select>
              </div>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: '20px' }}>
              {filteredEmployees.map((emp) => {
                  const myDeptId = userObj.id_departamento ? Number(userObj.id_departamento) : null;
                  const isAssignedThisMonthInMyDept = myDeptId && emp.assigned_dept_ids ? emp.assigned_dept_ids.includes(myDeptId) : false;
                  const isBaseOfMyDept = myDeptId && ((emp.base_dept_id && Number(emp.base_dept_id) === myDeptId) || (emp.dept_id && Number(emp.dept_id) === myDeptId));
                  const isMine = isAssignedThisMonthInMyDept || isBaseOfMyDept;
                  const isFree = (!emp.assigned_dept_ids || emp.assigned_dept_ids.length === 0) && (!emp.base_dept_id || emp.base_dept_name === 'Sin Área');
                  const isSelf = String(emp.id) === String(userObj.id_empleado);
                  const isEmpJefe = emp.is_jefe || ['Jefe de Area', 'Jefe de Servicio'].includes(emp.role);
                  
                  return (
                      <div key={emp.id} style={{ display: 'flex', flexDirection: 'column', padding: '20px', borderRadius: '14px', border: '1px solid', borderColor: isSelf ? '#bbf7d0' : (isMine ? '#c8e5ea' : (isFree ? '#fcd34d' : '#e2e8f0')), background: isSelf ? '#f0fdf4' : (isMine ? '#f3ffff' : (isFree ? '#fffbeb' : '#fff')), boxShadow: '0 4px 15px rgba(0,0,0,0.03)', transition: 'transform 0.2s', cursor: 'default' }} onMouseEnter={(e) => e.currentTarget.style.transform = 'translateY(-3px)'} onMouseLeave={(e) => e.currentTarget.style.transform = 'translateY(0)'}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '15px' }}>
                              <span style={{ padding: '4px 8px', borderRadius: '6px', fontSize: '10px', fontWeight: 'bold', letterSpacing: '0.05em', background: isSelf ? '#22c55e' : (isMine ? '#00a6a6' : (isFree ? '#f59e0b' : '#64748b')), color: '#fff' }}>
                                  {isSelf ? 'TU USUARIO' : (isMine ? 'EN TU ÁREA' : (isFree ? 'SIN ÁREA' : (emp.assigned_dept_names?.length > 0 ? emp.assigned_dept_names.join(' · ').toUpperCase() : (emp.base_dept_name || emp.dept).toUpperCase())))}
                              </span>
                              <span style={{ fontSize: '11px', fontFamily: 'monospace', color: '#64748b', background: '#f1f5f9', padding: '3px 7px', borderRadius: '4px' }}>{emp.bio}</span>
                          </div>
                          
                          <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '20px' }}>
                              <div style={{ width: '42px', height: '42px', borderRadius: '50%', background: isSelf ? '#dcfce7' : (isMine ? '#d6ffff' : '#f1f5f9'), color: isSelf ? '#16a34a' : (isMine ? '#00a6a6' : '#64748b'), display: 'grid', placeItems: 'center', fontWeight: 'bold', fontSize: '16px' }}>
                                  {emp.name.charAt(0).toUpperCase()}
                              </div>
                              <div>
                                  <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexWrap: 'wrap' }}>
                                      <strong style={{ color: 'var(--navy)', fontSize: '14px' }}>{emp.name} {isSelf && '(Tú)'}</strong>
                                      {isEmpJefe && (
                                          <span style={{ fontSize: '9px', background: '#eff6ff', color: '#1d4ed8', padding: '1px 5px', borderRadius: '4px', fontWeight: 'bold', border: '1px solid #bfdbfe' }}>
                                              JEFE
                                          </span>
                                      )}
                                  </div>
                                  <span style={{ display: 'block', color: 'var(--muted)', fontSize: '12px', marginTop: '2px' }}>
                                      {isEmpJefe 
                                          ? `${emp.role}: ${emp.base_dept_name || emp.dept || 'Sin Área'}` 
                                          : emp.role}
                                  </span>
                                  {emp.base_dept_name && emp.base_dept_name !== 'Sin Área' && !isEmpJefe && (
                                      <span style={{ display: 'block', color: '#64748b', fontSize: '11px', marginTop: '3px' }}>
                                          Área base: <strong>{emp.base_dept_name}</strong>
                                      </span>
                                  )}
                                  {emp.assigned_dept_names && emp.assigned_dept_names.length > 0 && (
                                      <span style={{ display: 'block', color: '#0284c7', fontSize: '11px', marginTop: '2px', fontWeight: '500' }}>
                                          Asignado este mes en: {emp.assigned_dept_names.join(' · ')}
                                      </span>
                                  )}
                              </div>
                          </div>
                          
                          <div style={{ marginTop: 'auto', display: 'flex', gap: '10px' }}>
                              {!isSelf && (
                                  isEmpJefe ? (
                                      <span style={{ width: '100%', padding: '8px', textAlign: 'center', borderRadius: '8px', background: '#f8fafc', color: '#64748b', fontSize: '11px', fontWeight: 'bold', border: '1px solid #e2e8f0' }}>
                                          Jefe titular del área
                                      </span>
                                  ) : userObj.rol === 'Administrador' ? (
                                      <>
                                          {emp.assigned_dept_ids && emp.assigned_dept_ids.length > 0 && (
                                              <button 
                                                  onClick={() => {
                                                      const currentFilterDeptObj = depts.find(d => d.nombre_departmento === filterDept);
                                                      const deptToRemove = currentFilterDeptObj ? Number(currentFilterDeptObj.id_departamento) : emp.assigned_dept_ids[0];
                                                      confirmRemove(emp.id, deptToRemove);
                                                  }} 
                                                  style={{ flex: 1, padding: '10px', borderRadius: '8px', border: '1px solid #fca5a5', background: '#fef2f2', color: '#ef4444', fontWeight: 'bold', fontSize: '12px', transition: 'background 0.2s' }} 
                                                  onMouseEnter={(e)=>e.currentTarget.style.background='#fee2e2'} 
                                                  onMouseLeave={(e)=>e.currentTarget.style.background='#fef2f2'}
                                              >
                                                  Quitar de su Área
                                              </button>
                                          )}
                                          <button onClick={() => openAssignModal(emp)} className="primary-button" style={{ flex: 1, padding: '10px', fontSize: '12px' }}>
                                              Asignar a un Área
                                          </button>
                                      </>
                                  ) : (
                                      <>
                                          {isAssignedThisMonthInMyDept ? (
                                              <button onClick={() => confirmRemove(emp.id, userObj.id_departamento)} style={{ flex: 1, padding: '10px', borderRadius: '8px', border: '1px solid #fca5a5', background: '#fef2f2', color: '#ef4444', fontWeight: 'bold', fontSize: '12px', transition: 'background 0.2s' }} onMouseEnter={(e)=>e.currentTarget.style.background='#fee2e2'} onMouseLeave={(e)=>e.currentTarget.style.background='#fef2f2'}>
                                                  Quitar de mi Área
                                              </button>
                                          ) : (
                                              <button onClick={() => openAssignModal(emp)} className="primary-button" style={{ flex: 1, padding: '10px', fontSize: '12px' }}>
                                                  Asignar a mi Área
                                              </button>
                                          )}
                                      </>
                                  )
                              )}
                          </div>
                      </div>
                  );
              })}
          </div>
          
          {filteredEmployees.length === 0 && <div style={{ padding: '60px 20px', textAlign: 'center', color: '#64748b', background: '#f8fafc', borderRadius: '12px', border: '1px dashed #cbd9e1' }}>
              <strong style={{ display: 'block', fontSize: '15px', color: 'var(--navy)', marginBottom: '5px' }}>No hay resultados</strong>
              Intenta ajustar los filtros o el texto de búsqueda.
          </div>}
      </section>

      {modalOpen && targetEmp && (
          <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
              <div style={{ background: '#fff', padding: '30px', borderRadius: '16px', width: '100%', maxWidth: '400px', boxShadow: '0 10px 25px rgba(0,0,0,0.2)' }}>
                  <h3 style={{ marginTop: 0, marginBottom: '5px', fontSize: '18px', color: 'var(--navy)' }}>
                      Asignar al Servicio
                  </h3>
                  <p style={{ color: 'var(--muted)', fontSize: '13px', marginBottom: '20px' }}>
                      Incorpora a <strong>{targetEmp.name}</strong> a este departamento para el mes seleccionado.
                  </p>
                  
                  <div style={{ marginBottom: '25px' }}>
                      <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: '#64748b', marginBottom: '8px' }}>Departamento Destino</label>
                      <select value={targetDept} onChange={(e) => setTargetDept(e.target.value)} disabled={userObj.rol !== 'Administrador'} style={{ width: '100%', padding: '12px', borderRadius: '8px', border: '1px solid #cbd9e1', background: userObj.rol !== 'Administrador' ? '#f1f5f9' : '#fff', fontWeight: 'bold', color: 'var(--navy)', fontSize: '14px' }}>
                          {depts.map(d => <option key={d.id_departamento} value={d.id_departamento}>{d.nombre_departmento}</option>)}
                      </select>
                      {userObj.rol !== 'Administrador' && (
                          <div style={{ fontSize: '11px', color: '#64748b', marginTop: '6px' }}>Solo puedes asignar empleados a tu propio departamento.</div>
                      )}
                  </div>
                  
                  <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end' }}>
                      <button onClick={() => { setModalOpen(false); setTargetEmp(null); }} style={{ padding: '10px 15px', borderRadius: '8px', border: 'none', background: '#f1f5f9', color: '#64748b', fontWeight: 'bold', cursor: 'pointer' }}>
                          Cancelar
                      </button>
                      <button onClick={confirmAssign} className="primary-button" style={{ padding: '10px 20px' }}>
                          Confirmar
                      </button>
                  </div>
              </div>
          </div>
      )}
      {alertMsg && (
          <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
              <div style={{ background: '#fff', padding: '30px', borderRadius: '16px', width: '100%', maxWidth: '400px', boxShadow: '0 10px 25px rgba(0,0,0,0.2)' }}>
                  <h3 style={{ marginTop: 0, marginBottom: '15px', fontSize: '18px', color: 'var(--navy)' }}>Atención</h3>
                  <p style={{ color: 'var(--muted)', fontSize: '14px', marginBottom: '25px', lineHeight: 1.5 }}>
                      {alertMsg}
                  </p>
                  <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                      <button onClick={() => setAlertMsg('')} className="primary-button" style={{ padding: '10px 20px' }}>
                          Entendido
                      </button>
                  </div>
              </div>
          </div>
      )}

      {removeModalOpen && (
          <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
              <div style={{ background: '#fff', padding: '30px', borderRadius: '16px', width: '100%', maxWidth: '400px', boxShadow: '0 10px 25px rgba(0,0,0,0.2)' }}>
                  <h3 style={{ marginTop: 0, marginBottom: '15px', fontSize: '18px', color: '#ef4444' }}>Confirmar acción</h3>
                  <p style={{ color: 'var(--muted)', fontSize: '14px', marginBottom: '25px', lineHeight: 1.5 }}>
                      ¿Seguro que deseas remover a este funcionario de este departamento para el mes seleccionado? Sus turnos en otras áreas (si tiene) no se verán afectados.
                  </p>
                  <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end' }}>
                      <button onClick={() => { setRemoveModalOpen(false); setEmpToRemove(null); setDeptToRemove(null); }} style={{ padding: '10px 15px', borderRadius: '8px', border: 'none', background: '#f1f5f9', color: '#64748b', fontWeight: 'bold', cursor: 'pointer' }}>
                          Cancelar
                      </button>
                      <button onClick={executeRemove} style={{ padding: '10px 20px', borderRadius: '8px', border: 'none', background: '#ef4444', color: '#fff', fontWeight: 'bold', cursor: 'pointer' }}>
                          Sí, remover
                      </button>
                  </div>
              </div>
          </div>
      )}
    </main>
  );
}

function AreasView({ setView }: { setView: (view: View) => void }) {
  const [depts, setDepts] = useState<any[]>([]);
  const [empleados, setEmpleados] = useState<any[]>([]);
  const [catalogList, setCatalogList] = useState<string[]>([
      'A.R.P.C. Y COMPRAS MENORES', 'ASESORIA JURIDICA', 'AUDITORIA INTERNA', 'CIRUGIA AMBULATORIA',
      'COMITE DE INFECCIONES', 'COMUNICACION SOCIAL Y RR PP', 'CONSULTA EXTERNA', 'DIRECCION',
      'DOCENCIA E INVESTIGACION', 'DPTO. ADM. FINANCIERO', 'DPTO. DE ALMACENES', 'DPTO. DE ARCHIVO Y ESTADISTICA',
      'DPTO. DE CIRUGIA', 'DPTO. DE COMPRAS Y ADQUISICIONES', 'DPTO. DE CONTABILIDAD', 'DPTO. DE EMERGENCIA',
      'DPTO. DE FINANZAS', 'DPTO. DE GINECOLOGIA', 'DPTO. DE MEDICINA', 'DPTO. DE PEDIATRIA',
      'DPTO. DE PLANIFICACION', 'DPTO. DE SERVICIOS GENERALES', 'DPTO. DE TECNOLOGIA DE INFORMACION',
      'DPTO. DE TERAPIA INTENSIVA', 'DPTO. RR HH', 'EDUCACION CONTINUA', 'ELECTROMEDICINA',
      'Emergencias', 'Enfermería', 'FARMACIA', 'FISIOTERAPIA', 'HEMODIALISIS', 'JEFATURA DE ENFERMERIA',
      'LABORATORIO', 'LAVANDERIA', 'MANTENIMIENTO', 'NUTRICION', 'ODONTOLOGIA', 'PATOLOGIA',
      'Pediatría', 'PROYECTO JICA', 'QUIROFANO', 'RAYOS X & IMAGENOLOGIA', 'S.U.S.',
      'SERVICIO BANCO DE SANGRE', 'SERVICIO DE ESTERILIZACION', 'SERVICIO SOCIAL', 'SUB DIRECCION AMBULATORIAL',
      'SUB DIRECCION HOSPITALIZACION', 'SUBDIRECCION ADMINISTRATIVA', 'Tecnología de la Información',
      'Traumatología', 'UNIDAD TRANSFUSIONAL'
  ]);
  const [selectedArea, setSelectedArea] = useState('');
  const [customAreaName, setCustomAreaName] = useState('');
  const [isCustom, setIsCustom] = useState(false);
  const [selectedEmpleadoId, setSelectedEmpleadoId] = useState('');
  const [alertMsg, setAlertMsg] = useState('');
  const [searchModalOpen, setSearchModalOpen] = useState(false);
  const [empSearchTerm, setEmpSearchTerm] = useState('');

  const load = () => {
      fetch(getApiUrl('/api/departamentos')).then(r=>r.json()).then(data => setDepts(data));
      fetch(getApiUrl('/api/empleados/all')).then(r=>r.json()).then(data => setEmpleados(data));
      fetch(getApiUrl('/api/departamentos/catalogo')).then(r=>r.json()).then(data => {
          if (Array.isArray(data) && data.length > 0) {
              setCatalogList(data);
          }
      });
  }
  useEffect(() => { load() }, []);

  const availableCatalog = useMemo(() => {
      const existingNames = new Set(depts.map(d => (d.nombre_departmento || '').trim().toLowerCase()));
      return catalogList.filter(name => !existingNames.has(name.trim().toLowerCase()));
  }, [catalogList, depts]);

  const createArea = async (e: React.FormEvent) => {
      e.preventDefault();
      const finalName = (isCustom ? customAreaName : selectedArea).trim();
      if (!finalName) {
          setAlertMsg('Selecciona un área del listado o ingresa un nombre.');
          return;
      }

      // Restricción para que no se repitan las áreas (validación preventiva en frontend)
      const yaExiste = depts.some(
          d => d.nombre_departmento && d.nombre_departmento.trim().toLowerCase() === finalName.toLowerCase()
      );
      if (yaExiste) {
          setAlertMsg(`El área "${finalName}" ya existe en el sistema. No se permiten áreas repetidas.`);
          return;
      }
      
      const res = await fetch(getApiUrl('/api/departamentos'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ nombre_departmento: finalName, jefe_empleado_id: selectedEmpleadoId || null })
      });
      
      const data = await res.json();
      if (res.ok) {
          let msg = `Área "${finalName}" creada exitosamente.`;
          if (data.created_username) {
              msg += ` Se ha generado un acceso para el Jefe con el código de ingreso: ${data.created_username}`;
          }
          setAlertMsg(msg);
          setSelectedArea('');
          setCustomAreaName('');
          setIsCustom(false);
          setSelectedEmpleadoId('');
          load();
      } else {
          setAlertMsg(data.error || 'Error al crear el área.');
      }
  }

  return (
    <main className="content">
      <div className="page-heading">
        <div>
          <span className="eyebrow">ADMINISTRACIÓN</span>
          <h1>Gestión de Áreas</h1>
          <p>Crea nuevas áreas y asigna cualquier empleado como Jefe (su usuario se creará automáticamente).</p>
        </div>
      </div>
      
      <section className="form-card" style={{ marginBottom: '20px' }}>
          <div className="form-card-heading">
              <div>
                  <h2>Crear Nueva Área</h2>
                  <p>Selecciona el área o departamento oficial que deseas habilitar y opcionalmente elige su Jefe.</p>
              </div>
          </div>
          <form onSubmit={createArea} style={{ display: 'flex', gap: '15px', alignItems: 'flex-start', padding: '20px', flexWrap: 'wrap' }}>
              <div style={{ flex: 1, minWidth: '280px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: '#64748b', marginBottom: '8px' }}>
                      Nombre del Área / Servicio ({availableCatalog.length} disponibles)
                  </label>
                  <select 
                      value={isCustom ? '__CUSTOM__' : selectedArea} 
                      onChange={e => {
                          if (e.target.value === '__CUSTOM__') {
                              setIsCustom(true);
                          } else {
                              setIsCustom(false);
                              setSelectedArea(e.target.value);
                          }
                      }} 
                      style={{ width: '100%', padding: '12px', borderRadius: '8px', border: '1px solid #cbd9e1', background: '#fff', fontSize: '13px', outline: 'none', color: (isCustom ? customAreaName : selectedArea) ? 'var(--navy)' : '#64748b', fontWeight: (isCustom ? customAreaName : selectedArea) ? '500' : 'normal' }}
                  >
                      <option value="">-- Seleccionar Área / Servicio Hospitalario --</option>
                      {availableCatalog.map(areaName => (
                          <option key={areaName} value={areaName}>
                              {areaName}
                          </option>
                      ))}
                      <option value="__CUSTOM__">+ Otro (Escribir nombre manualmente)...</option>
                  </select>
                  {isCustom && (
                      <input 
                          type="text" 
                          autoFocus
                          value={customAreaName} 
                          onChange={e => setCustomAreaName(e.target.value)} 
                          placeholder="Escribe el nombre del área..." 
                          style={{ width: '100%', marginTop: '8px', padding: '10px 12px', borderRadius: '8px', border: '1px solid #0284c7', background: '#f8fafc', fontSize: '13px' }} 
                      />
                  )}
              </div>
              <div style={{ flex: 1, minWidth: '280px' }}>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 'bold', color: '#64748b', marginBottom: '8px' }}>
                      Asignar Empleado como Jefe (Opcional)
                  </label>
                  <button type="button" onClick={() => setSearchModalOpen(true)} style={{ width: '100%', padding: '12px', borderRadius: '8px', border: '1px solid #cbd9e1', background: '#fff', textAlign: 'left', display: 'flex', justifyContent: 'space-between', alignItems: 'center', cursor: 'pointer', color: selectedEmpleadoId ? '#0f172a' : '#64748b' }}>
                      {selectedEmpleadoId ? (empleados.find(e => String(e.id_empleado) === String(selectedEmpleadoId))?.nombre_completo || 'Seleccionado') : '-- Seleccionar Empleado --'}
                      <Search size={16} color="#64748b" />
                  </button>
              </div>
              <button type="submit" className="primary-button" style={{ padding: '12px 20px', whiteSpace: 'nowrap', marginTop: '23px', height: '44px' }}>
                  Crear Área
              </button>
          </form>
      </section>

      <section className="report-list-card" style={{ padding: '20px' }}>
          <h3 style={{ marginTop: 0, marginBottom: '15px', color: 'var(--navy)' }}>Áreas Existentes ({depts.length})</h3>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: '15px' }}>
              {depts.map(d => {
                  const jefe = d.empleados && d.empleados.length > 0 ? d.empleados[0] : null;
                  return (
                      <div key={d.id_departamento} style={{ padding: '16px', border: '1px solid #e2e8f0', borderRadius: '12px', background: '#f8fafc', display: 'flex', flexDirection: 'column', gap: '6px', transition: 'box-shadow 0.2s, transform 0.2s' }}>
                          <strong style={{ display: 'block', color: 'var(--navy)', fontSize: '14px', marginBottom: '2px' }}>{d.nombre_departmento}</strong>
                          <span style={{ fontSize: '12px', color: '#64748b' }}>
                              Jefe: {jefe ? <strong style={{ color: '#0f172a' }}>{jefe.nombre_completo}</strong> : <span style={{ fontStyle: 'italic' }}>Sin jefe asignado</span>}
                          </span>
                          {jefe && (
                              <div style={{ marginTop: '4px', display: 'flex', alignItems: 'center', gap: '6px' }}>
                                  <span style={{ fontSize: '11px', color: '#0369a1', background: '#e0f2fe', padding: '3px 8px', borderRadius: '6px', fontWeight: 'bold', display: 'inline-flex', alignItems: 'center', gap: '4px', border: '1px solid #bae6fd' }}>
                                      Código para planilla: <span style={{ fontFamily: 'monospace', fontSize: '12px', color: '#0284c7' }}>{jefe.codigo_biometrico}</span>
                                  </span>
                              </div>
                          )}
                      </div>
                  )
              })}
          </div>
      </section>

      {alertMsg && (
          <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
              <div style={{ background: '#fff', padding: '30px', borderRadius: '16px', width: '100%', maxWidth: '400px', boxShadow: '0 10px 25px rgba(0,0,0,0.2)' }}>
                  <h3 style={{ marginTop: 0, marginBottom: '15px', fontSize: '18px', color: 'var(--navy)' }}>Atención</h3>
                  <p style={{ color: 'var(--muted)', fontSize: '14px', marginBottom: '25px', lineHeight: 1.5 }}>
                      {alertMsg}
                  </p>
                  <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
                      <button onClick={() => setAlertMsg('')} className="primary-button" style={{ padding: '10px 20px' }}>
                          Entendido
                      </button>
                  </div>
              </div>
          </div>
      )}

      {searchModalOpen && (
          <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
              <div style={{ background: '#fff', padding: '25px', borderRadius: '16px', width: '100%', maxWidth: '500px', boxShadow: '0 10px 25px rgba(0,0,0,0.2)' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                      <h3 style={{ margin: 0, fontSize: '18px', color: 'var(--navy)' }}>Buscar Empleado</h3>
                      <button onClick={() => setSearchModalOpen(false)} style={{ background: 'none', border: 'none', cursor: 'pointer', padding: '5px' }}><X size={20} color="#64748b" /></button>
                  </div>
                  <input autoFocus type="text" placeholder="Buscar por código (ej. JEF-004) o nombre..." value={empSearchTerm} onChange={e => setEmpSearchTerm(e.target.value)} style={{ width: '100%', padding: '12px', borderRadius: '8px', border: '1px solid #cbd9e1', marginBottom: '15px', fontSize: '14px' }} />
                  <div style={{ maxHeight: '300px', overflowY: 'auto', border: '1px solid #e2e8f0', borderRadius: '8px' }}>
                      <button type="button" onClick={() => { setSelectedEmpleadoId(''); setSearchModalOpen(false); }} style={{ width: '100%', padding: '12px', textAlign: 'left', border: 'none', borderBottom: '1px solid #e2e8f0', background: '#f8fafc', cursor: 'pointer' }}>
                          <strong style={{ color: '#64748b' }}>-- Ninguno (Crear área sin jefe) --</strong>
                      </button>
                      {empleados.filter(e => e.nombre_completo.toLowerCase().includes(empSearchTerm.toLowerCase()) || e.codigo_biometrico.toLowerCase().includes(empSearchTerm.toLowerCase())).map(emp => (
                          <button type="button" key={emp.id_empleado} onClick={() => { setSelectedEmpleadoId(emp.id_empleado); setSearchModalOpen(false); }} style={{ width: '100%', padding: '12px', textAlign: 'left', border: 'none', borderBottom: '1px solid #e2e8f0', background: '#fff', cursor: 'pointer', display: 'flex', flexDirection: 'column', transition: 'background 0.2s' }} onMouseEnter={e => e.currentTarget.style.background = '#f1f5f9'} onMouseLeave={e => e.currentTarget.style.background = '#fff'}>
                              <strong style={{ color: 'var(--navy)', fontSize: '14px', marginBottom: '2px' }}>{emp.nombre_completo}</strong>
                              <span style={{ fontSize: '12px', color: '#64748b' }}>Cód: <span style={{ fontFamily: 'monospace', background: '#e2e8f0', padding: '2px 4px', borderRadius: '4px' }}>{emp.codigo_biometrico}</span> · {emp.departamento ? `Área actual: ${emp.departamento.nombre_departmento}` : 'Sin área'}</span>
                          </button>
                      ))}
                      {empleados.filter(e => e.nombre_completo.toLowerCase().includes(empSearchTerm.toLowerCase()) || e.codigo_biometrico.toLowerCase().includes(empSearchTerm.toLowerCase())).length === 0 && (
                          <div style={{ padding: '20px', textAlign: 'center', color: '#64748b', fontSize: '13px' }}>No se encontraron empleados con ese término.</div>
                      )}
                  </div>
              </div>
          </div>
      )}
    </main>
  );
}

export default function Page() {
  const [view, setView] = useState<View>('login')
  const [userObj, setUserObj] = useState<User | null>(null)
  const [people, setPeople] = useState<Person[]>([])
  
  const currentMonth = new Date().getMonth();
  const currentYear = new Date().getFullYear().toString();
  const [role, setRole] = useState<RoleMeta>({ year: currentYear, month: monthNames[currentMonth], service: 'Cargando...', serviceId: null })
  
  useEffect(() => {
      if(userObj && view !== 'login' && view !== 'assign') {
          // fetch people based on service
          const m = monthNames.indexOf(role.month) + 1;
          let qs = `?mes=${m}&anio=${role.year}`;
          if (role.serviceId && role.serviceId !== 'todos') qs += `&departamento_id=${role.serviceId}`;
          else if (!role.serviceId && userObj.id_departamento) qs += `&departamento_id=${userObj.id_departamento}`;
          
          fetch(getApiUrl('/api/empleados') + qs).then(r=>r.json()).then(data => {
              if (Array.isArray(data)) {
                  setPeople(data);
              }
          });
      }
  }, [role.serviceId, role.month, role.year, userObj, view]);

  const current = useMemo(() => view, [view])
  if (current === 'login' || !userObj) return <Login onLogin={(u: any) => { setUserObj(u); setView('dashboard') }} />
  return <AppShell view={current} setView={setView} userObj={userObj}>
    {current === 'dashboard' && <Dashboard setView={setView} userObj={userObj} people={people} role={role} />}
    {current === 'editor' && <Editor setView={setView} people={people} setPeople={setPeople} role={role} setRole={setRole} userObj={userObj} />}
    {current === 'assign' && <AssignView setView={setView} userObj={userObj} role={role} setRole={setRole} />}
    {current === 'areas' && <AreasView setView={setView} />}
    {current === 'reports' && <Reports setView={setView} people={people} role={role} setRole={setRole} userObj={userObj} />}
    {current === 'history' && <HistoryView setView={setView} role={role} setRole={setRole} userObj={userObj} />}
    {current === 'report' && <Report setView={setView} people={people} role={role} />}
  </AppShell>
}
