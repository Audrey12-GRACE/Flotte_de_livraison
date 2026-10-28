export const useCourses = () => {
  const api = useRuntimeConfig().public.apiBase
  const headers = { Accept: 'application/json' }

  const courses = useState('courses', () => [])
  const livreurs = useState('livreurs', () => [])
  const stats = useState('stats', () => ({
    ca_total: 0,
    courses_livrees: 0,
    courses_en_cours: 0,
    courses_en_attente: 0,
    courses_annulees: 0,
    livreurs_actifs: 0,
    livreurs_total: 0
  }))

  const charger = async () => {
    const [c, l, s] = await Promise.all([
      $fetch(`${api}/courses`, { headers }),
      $fetch(`${api}/livreurs`, { headers }),
      $fetch(`${api}/stats`, { headers })
    ])
    courses.value = c
    livreurs.value = l
    stats.value = s
  }

  // Courses
  const creerCourse = async (data) => {
    await $fetch(`${api}/courses`, { method: 'POST', body: data, headers })
    await charger()
  }
  const modifierCourse = async (id, data) => {
    await $fetch(`${api}/courses/${id}`, { method: 'PUT', body: data, headers })
    await charger()
  }
  const avancer = async (course) => {
    await $fetch(`${api}/courses/${course.id}/avancer`, { method: 'PATCH', headers })
    await charger()
  }
  const annuler = async (course) => {
    await $fetch(`${api}/courses/${course.id}/annuler`, { method: 'PATCH', headers })
    await charger()
  }

  // Livreurs
  const creerLivreur = async (data) => {
    await $fetch(`${api}/livreurs`, { method: 'POST', body: data, headers })
    await charger()
  }
  const modifierLivreur = async (id, data) => {
    await $fetch(`${api}/livreurs/${id}`, { method: 'PUT', body: data, headers })
    await charger()
  }
  const retirerLivreur = async (id) => {
    await $fetch(`${api}/livreurs/${id}`, { method: 'DELETE', headers })
    await charger()
  }

  return {
    courses, livreurs, stats, charger,
    creerCourse, modifierCourse, avancer, annuler,
    creerLivreur, modifierLivreur, retirerLivreur
  }
}