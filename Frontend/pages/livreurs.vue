<template>
  <main class="page">
    <div class="container">
      <AppHeader />
      <section class="intro">
        <div>
          <p class="eyebrow">ÉQUIPE & FLOTTE</p>
          <h1>Gestion des livreurs</h1>
          <p>Enregistrez les membres de votre équipe et leurs informations de tournée.</p>
        </div>
        <button class="button primary" type="button" @click="ouvrirCreation">Ajouter un livreur</button>
      </section>

      <section class="panel">
        <div class="panel-heading">
          <div>
            <h2>Votre équipe</h2>
            <p>{{ livreurs.length }} {{ livreurs.length > 1 ? 'livreurs enregistrés' : 'livreur enregistré' }}</p>
          </div>
          <label class="search">
            <input v-model="recherche" type="search" placeholder="Rechercher un livreur">
          </label>
        </div>

        <div v-if="livreursFiltres.length" class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Livreur</th>
                <th>Téléphone</th>
                <th>Véhicule</th>
                <th>Zone</th>
                <th>Courses actives</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="livreur in livreursFiltres" :key="livreur.id">
                <td>
                  <div class="person">
                    <span class="avatar">{{ initiales(livreur.nom) }}</span>
                    <strong>{{ livreur.nom }}</strong>
                  </div>
                </td>
                <td>{{ livreur.telephone || '—' }}</td>
                <td>{{ livreur.vehicule || '—' }}</td>
                <td>{{ livreur.zone || '—' }}</td>
                <td>
                  <span class="active-count">{{ nombreCoursesActives(livreur.id) }}</span>
                </td>
                <td>
                  <div class="actions">
                    <button class="edit-button" type="button" @click="ouvrirModification(livreur)">Modifier</button>
                    <button class="remove-button" type="button" @click="retirer(livreur)">Retirer</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="empty-state">
          <h3>{{ livreurs.length ? 'Aucun résultat' : 'Aucun livreur enregistré' }}</h3>
          <p v-if="livreurs.length">Essayez un autre nom ou numéro.</p>
          <p v-else>Ajoutez votre équipe pour pouvoir affecter des courses.</p>
          <button v-if="!livreurs.length" class="button primary" type="button" @click="ouvrirCreation">
            Ajouter un livreur
          </button>
        </div>
      </section>

      <p v-if="message" class="notice" role="status">{{ message }}</p>

      <div v-if="dialogueOuvert" class="backdrop" @click.self="fermer">
        <section class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
          <button class="close" type="button" @click="fermer">Fermer</button>
          <p class="eyebrow">{{ livreurModifie ? 'MISE À JOUR DE L’ÉQUIPE' : 'NOUVEAU MEMBRE' }}</p>
          <h2 id="dialog-title">{{ livreurModifie ? 'Modifier le livreur' : 'Ajouter un livreur' }}</h2>
          <p class="dialog-copy">Les champs facultatifs pourront être complétés ultérieurement.</p>
          <form class="form" @submit.prevent="enregistrer">
            <label for="nom">Nom complet <span>*</span></label>
            <input id="nom" v-model="form.nom" required autocomplete="name" placeholder="Nom du livreur">
            <label for="telephone">Téléphone</label>
            <input id="telephone" v-model="form.telephone" type="tel" autocomplete="tel"
            placeholder="+228 00 00 00 00"
            pattern="(\+228\s?)?\d{2}(\s?\d{2}){3}"
            title="Format attendu : +228 00 00 00 00"
            @input="form.telephone = form.telephone.replace(/[^\d+\s]/g, '')">
            <div class="form-row">
              <div>
                <label for="vehicule">Véhicule</label>
                <input id="vehicule" v-model="form.vehicule" placeholder="Ex. Moto">
              </div>
              <div>
                <label for="zone">Zone</label>
                <input id="zone" v-model="form.zone" placeholder="Ex. Lomé">
              </div>
            </div>
            <p v-if="erreurFormulaire" class="form-error" role="alert">{{ erreurFormulaire }}</p>
            <div class="dialog-actions">
              <button class="button secondary" type="button" @click="fermer">Annuler</button>
              <button class="button primary" type="submit">{{ livreurModifie ? 'Enregistrer' : 'Ajouter' }}</button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </main>
</template>

<script setup>
const apiBase = useRuntimeConfig().public.apiBase

const livreurs = ref([])
const courses = ref([])

const recherche = ref('')
const dialogueOuvert = ref(false)
const livreurModifie = ref(null)
const message = ref('')
const erreurFormulaire = ref('')
const form = ref({ nom: '', telephone: '', vehicule: '', zone: '' })

async function charger() {
  try {
    livreurs.value = await $fetch(apiBase + '/livreurs')
    courses.value = await $fetch(apiBase + '/courses')
  } catch (e) {
    console.log('erreur chargement des livreurs', e)
  }
}

onMounted(charger)

const livreursFiltres = computed(() => {
  const terme = recherche.value.trim().toLowerCase()
  if (!terme) return livreurs.value

  return livreurs.value.filter(l => {
    const texte = [l.nom, l.telephone, l.vehicule, l.zone].join(' ').toLowerCase()
    return texte.includes(terme)
  })
})

function nombreCoursesActives(id) {
  let total = 0
  for (const c of courses.value) {
    if (c.livreur_id === id && (c.statut === 'en_attente' || c.statut === 'en_cours')) {
      total++
    }
  }
  return total
}

function initiales(nom) {
  const mots = nom.trim().split(' ')
  let resultat = mots[0][0]
  if (mots.length > 1) resultat += mots[1][0]
  return resultat.toUpperCase()
}

function ouvrirCreation() {
  livreurModifie.value = null
  form.value = { nom: '', telephone: '', vehicule: '', zone: '' }
  erreurFormulaire.value = ''
  dialogueOuvert.value = true
}

function ouvrirModification(livreur) {
  livreurModifie.value = livreur
  form.value = {
    nom: livreur.nom,
    telephone: livreur.telephone || '',
    vehicule: livreur.vehicule || '',
    zone: livreur.zone || ''
  }
  erreurFormulaire.value = ''
  dialogueOuvert.value = true
}

function fermer() {
  dialogueOuvert.value = false
}

async function enregistrer() {
  erreurFormulaire.value = ''
  message.value = ''

  try {
    if (livreurModifie.value) {
      await $fetch(apiBase + '/livreurs/' + livreurModifie.value.id, { method: 'PATCH', body: form.value })
    } else {
      await $fetch(apiBase + '/livreurs', { method: 'POST', body: form.value })
    }
    await charger()
    fermer()
  } catch (e) {
    erreurFormulaire.value = e?.data?.message || 'Impossible d’enregistrer le livreur.'
  }
}

async function retirer(livreur) {
  message.value = ''
  if (!confirm('Retirer ' + livreur.nom + ' de la flotte ?')) return

  try {
    await $fetch(apiBase + '/livreurs/' + livreur.id, { method: 'DELETE' })
    await charger()
  } catch (e) {
    message.value = 'Impossible de retirer ce livreur.'
  }
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.container {
  width: min(1160px, 100%);
  margin: 0 auto;
  padding: 0 24px 56px;
}

.intro {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 38px 0 24px;
}

.eyebrow {
  margin: 0 0 7px;
  color: #7c879a;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .12em;
}

.intro h1 {
  margin: 0;
  color: #172033;
  font-size: clamp(26px, 4vw, 34px);
  letter-spacing: -.04em;
}

.intro p:last-child {
  margin: 8px 0 0;
  color: #7b8699;
  font-size: 14px;
}

.button {
  display: inline-flex;
  min-height: 40px;
  align-items: center;
  justify-content: center;
  padding: 0 14px;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
}

.primary {
  background: #4563df;
  color: #fff;
}

.primary:hover {
  background: #354fca;
}

.secondary {
  border: 1px solid #e1e6ef;
  background: #fff;
  color: #58647a;
}

.panel {
  overflow: hidden;
  border: 1px solid #e9edf4;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 8px 30px rgb(32 48 85 / 4%);
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 22px;
}

.panel-heading h2 {
  margin: 0;
  color: #202b40;
  font-size: 17px;
}

.panel-heading p {
  margin: 5px 0 0;
  color: #8791a3;
  font-size: 12px;
}

.search {
  display: flex;
  width: min(300px, 35vw);
  height: 38px;
  align-items: center;
  padding: 0 10px;
  border: 1px solid #e1e6ef;
  border-radius: 8px;
}

.search input {
  width: 100%;
  border: 0;
  outline: 0;
  color: #303b50;
  font: inherit;
  font-size: 11px;
}

.table-scroll {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  white-space: nowrap;
}

thead {
  background: #f8f9fc;
}

th {
  padding: 12px 18px;
  color: #7c879a;
  font-size: 10px;
  font-weight: 750;
  letter-spacing: .07em;
  text-transform: uppercase;
}

td {
  padding: 15px 18px;
  border-top: 1px solid #edf0f5;
  color: #59657a;
  font-size: 12px;
}

.person {
  display: flex;
  align-items: center;
  gap: 10px;
}

.person strong {
  color: #303b50;
  font-size: 12px;
}

.avatar {
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  border-radius: 11px;
  background: #edf1ff;
  color: #4563df;
  font-size: 11px;
  font-weight: 750;
}

.active-count {
  display: inline-grid;
  min-width: 24px;
  height: 24px;
  place-items: center;
  border-radius: 8px;
  background: #f1f3f8;
  color: #59657a;
  font-size: 11px;
  font-weight: 700;
}

.actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.edit-button,
.remove-button {
  height: 30px;
  padding: 0 9px;
  border: 0;
  border-radius: 7px;
  cursor: pointer;
  font: inherit;
  font-size: 11px;
  font-weight: 650;
}

.edit-button {
  background: #f1f3f8;
  color: #66748b;
}

.remove-button {
  background: #fff0ed;
  color: #bd5947;
}

.empty-state {
  display: flex;
  min-height: 280px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  text-align: center;
}

.empty-state h3 {
  margin: 0 0 5px;
  color: #303b50;
  font-size: 15px;
}

.empty-state p {
  margin: 0 0 16px;
  color: #8791a3;
  font-size: 12px;
}

.notice {
  padding: 12px 14px;
  border-radius: 8px;
  background: #fff5df;
  color: #946116;
  font-size: 12px;
}

.backdrop {
  position: fixed;
  z-index: 10;
  inset: 0;
  display: grid;
  place-items: center;
  overflow-y: auto;
  padding: 20px;
  background: rgb(23 32 51 / 45%);
}

.dialog {
  position: relative;
  width: min(480px, 100%);
  padding: 26px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 20px 70px rgb(15 25 50 / 20%);
}

.close {
  position: absolute;
  top: 16px;
  right: 17px;
  padding: 7px 10px;
  border: 0;
  border-radius: 8px;
  background: #f1f3f8;
  color: #66748b;
  cursor: pointer;
  font: inherit;
  font-size: 11px;
  font-weight: 650;
}

.dialog h2 {
  margin: 0;
  color: #202b40;
  font-size: 21px;
}

.dialog-copy {
  margin: 7px 0 20px;
  color: #8791a3;
  font-size: 12px;
}

.form {
  display: grid;
  gap: 8px;
}

.form label {
  margin-top: 4px;
  color: #48546a;
  font-size: 11px;
  font-weight: 650;
}

.form label span {
  color: #bd5947;
}

.form input {
  width: 100%;
  height: 40px;
  padding: 0 10px;
  border: 1px solid #e1e6ef;
  border-radius: 8px;
  outline: none;
  font: inherit;
  font-size: 12px;
}

.form input:focus {
  border-color: #7086ed;
  box-shadow: 0 0 0 3px rgb(69 99 223 / 12%);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-row>div {
  display: grid;
  gap: 8px;
}

.form-error {
  margin: 2px 0 0;
  color: #bd5947;
  font-size: 11px;
}

.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 14px;
}

@media (max-width: 720px) {
  .container {
    padding-right: 16px;
    padding-left: 16px;
  }

  .panel-heading {
    align-items: flex-start;
    flex-direction: column;
    padding: 18px;
  }

  .search {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .intro {
    align-items: flex-start;
    flex-direction: column;
    padding-top: 28px;
  }

  .intro .button {
    width: 100%;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .dialog {
    padding: 22px 18px;
  }
}
</style>