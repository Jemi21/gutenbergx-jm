<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guten Books</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; }
        header { padding: 16px; border-bottom: 1px solid #eee; position: sticky; top: 0; background: #fff; z-index: 10; }
        .container { max-width: 960px; margin: 0 auto; padding: 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        .btn { padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; background: #f8f8f8; cursor: pointer; text-align: center; }
        .btn:hover { background: #f0f0f0; }
        .card { border: 1px solid #eee; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
        .card img { width: 100%; height: 200px; object-fit: cover; background: #fafafa; }
        .card .content { padding: 10px; display: flex; flex-direction: column; gap: 6px; }
        .search { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; }
        .badge { display: inline-block; padding: 2px 8px; background: #eef2ff; color: #3730a3; border-radius: 999px; font-size: 12px; margin-right: 6px; }
        .muted { color: #666; font-size: 12px; }
        .center { text-align: center; padding: 24px; color: #555; }
    </style>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://unpkg.com/vue-router@4"></script>
</head>
<body>
<div id="app">
    <router-view></router-view>
    <div v-if="toast" class="center" v-text="toast"></div>
    <div ref="sentinel" style="height: 1px"></div>
  </div>

<script>
const preferFormats = [
  'text/html',
  'application/pdf',
  'text/plain',
];

function chooseViewableUrl(downloadLinks) {
  if (!Array.isArray(downloadLinks)) return null;
  for (const pref of preferFormats) {
    const found = downloadLinks.find(l => l.mime_type && l.mime_type.startsWith(pref));
    if (found && found.url && !/\.zip($|\?)/i.test(found.url)) return found.url;
  }
  return null;
}

function hasCover(downloadLinks) {
  return Array.isArray(downloadLinks) && downloadLinks.some(l => l.mime_type && l.mime_type.startsWith('image/'));
}

const Home = {
  name: 'Home',
  data: () => ({ loading: true, genres: [], error: '' }),
  async mounted() {
    try {
      const res = await fetch('/api/genres');
      const data = await res.json();
      this.genres = (data.results || []).slice(0, 60);
    } catch (e) {
      this.error = 'Failed to load genres';
    } finally {
      this.loading = false;
    }
  },
  template: `
    <div>
      <header><div class="container"><h1>Guten Books</h1><div class="muted">Pick a genre to browse</div></div></header>
      <div class="container">
        <div v-if="loading" class="center">Loading…</div>
        <div v-else class="grid">
          <button class="btn" v-for="g in genres" :key="g" @click="$router.push({ name: 'Books', params: { genre: g }})">@{{ g }}</button>
        </div>
        <div v-if="error" class="center">@{{ error }}</div>
      </div>
    </div>
  `,
};

const Books = {
  name: 'Books',
  data: () => ({
    items: [],
    nextUrl: null,
    loading: false,
    query: '',
    toast: '',
  }),
  computed: {
    genre() { return this.$route.params.genre; },
    title() { return this.query.trim(); },
  },
  methods: {
    async loadFirst() {
      this.items = [];
      this.nextUrl = this.buildUrl(1);
      await this.loadMore();
    },
    authorNames(book) {
      const arr = Array.isArray(book.authors) ? book.authors : [];
      return arr.map(function(a){ return a && a.name ? a.name : ''; }).filter(Boolean).join(', ');
    },
    coverUrl(book) {
      const links = Array.isArray(book.download_links) ? book.download_links : [];
      for (let i = 0; i < links.length; i++) {
        const l = links[i];
        if (l && l.mime_type && l.mime_type.indexOf('image/') === 0 && l.url) {
          return l.url;
        }
      }
      return '';
    },
    buildUrl(page) {
      const p = new URLSearchParams();
      p.set('page', page);
      p.set('limit', 25);
      p.set('topic', this.genre);
      p.set('has_cover', '1');
      if (this.title) {
        p.set('search', this.title);
      }
      return `/api/books?${p.toString()}`;
    },
    async loadMore() {
      if (this.loading || !this.nextUrl) return;
      this.loading = true;
      try {
        const res = await fetch(this.nextUrl);
        const data = await res.json();
        const pageItems = (data.results || []).filter(b => hasCover(b.download_links));
        this.items.push(...pageItems);
        this.nextUrl = data.next;
      } catch (e) {
        this.$root.toast = 'Failed to load books';
        setTimeout(() => this.$root.toast = '', 2000);
      } finally {
        this.loading = false;
      }
    },
    openBook(book) {
      const url = chooseViewableUrl(book.download_links);
      if (url) {
        window.open(url, '_blank');
      } else {
        alert('No viewable version available');
      }
    },
    onSearch() {
      this.loadFirst();
    }
  },
  async mounted() {
    await this.loadFirst();
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) this.loadMore(); });
    });
    io.observe(this.$root.$refs.sentinel);
  },
  watch: {
    genre() { this.loadFirst(); },
    query: {
      handler: function() {
        clearTimeout(this._qT);
        const self = this;
        this._qT = setTimeout(function(){ self.loadFirst(); }, 300);
      }
    }
  },
  template: `
    <div>
      <header>
        <div class="container">
          <div style="display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
            <div>
              <button class="btn" @click="$router.push({ name: 'Home' })">← Genres</button>
            </div>
            <div style="flex:1;min-width:220px;max-width:520px;">
              <input class="search" v-model="query" @keyup.enter="onSearch" placeholder="Search by title or author…"/>
            </div>
            <div class="muted">@{{ genre }}</div>
          </div>
        </div>
      </header>
      <div class="container">
        <div class="grid">
          <div class="card" v-for="b in items" :key="b.id" @click="openBook(b)" style="cursor:pointer;">
            <img :src="coverUrl(b)" alt="cover"/>
            <div class="content">
              <div style="font-weight:600;">@{{ b.title }}</div>
              <div class="muted" v-text="authorNames(b)"></div>
              <div>
                <span class="badge" v-for="s in (b.bookshelves||[]).slice(0,2)" :key="s">@{{ s }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-if="loading" class="center">Loading…</div>
        <div v-if="!loading && !nextUrl && items.length===0" class="center">No results</div>
      </div>
    </div>
  `,
};

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/genre/:genre', name: 'Books', component: Books },
];
const router = VueRouter.createRouter({ history: VueRouter.createWebHistory(), routes });
const app = Vue.createApp({ data: ()=>({ toast: '' }) });
app.use(router);
app.mount('#app');
</script>
</body>
</html>


