/* ============================================================================
   DreamTour — Alpine.js bootstrap
   ----------------------------------------------------------------------------
   Registers shared stores and reusable Alpine components. This file is loaded
   BEFORE vendor/alpine.min.js, so everything hangs off the `alpine:init` event.

   Page-specific behaviour should be written declaratively in the HTML with
   x-data. Only genuinely reusable logic belongs here.
   ========================================================================== */

document.addEventListener("alpine:init", () => {
  const postRequest = async (payload) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const response = await fetch('/booking-requests', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token || '' },
      body: JSON.stringify(payload),
    });
    if (!response.ok) {
      const body = await response.json().catch(() => ({}));
      throw new Error(Object.values(body.errors || {}).flat()[0] || body.message || 'Request could not be sent.');
    }
  };
  const person = (name) => { const parts = name.trim().split(/\s+/); return { first_name: parts.shift() || '', last_name: parts.join(' ') || '-' }; };
  /* --------------------------------------------------------------------------
     Global UI store — cross-component state (mobile nav, active dropdowns …).
     ------------------------------------------------------------------------ */
  Alpine.store("ui", {
    mobileNav: false,
    openMenu: null, // key of the currently open desktop dropdown / mega-menu

    toggleMobileNav() {
      this.mobileNav = !this.mobileNav;
      document.body.classList.toggle("overflow-hidden", this.mobileNav);
    },
    closeMobileNav() {
      this.mobileNav = false;
      document.body.classList.remove("overflow-hidden");
    },
    toggleMenu(key) {
      this.openMenu = this.openMenu === key ? null : key;
    },
    closeMenus() {
      this.openMenu = null;
    },
  });

  /* --------------------------------------------------------------------------
     Home hero search bar (§5 filters, condensed). Builds a query string and
     hands off to the tour listing page — no real search in Phase 1.
     ------------------------------------------------------------------------ */
  Alpine.data("heroSearch", () => ({
    destination: "",
    date: "",
    type: "",
    typeOpen: false,
    types: [
      "Any Type",
      "One-Day Tours",
      "Multi-Day Tours",
      "International Tours",
      "Cruises",
      "Private Tours",
    ],
    get typeLabel() {
      return this.type || "Any Type";
    },
    pickType(t) {
      this.type = t === "Any Type" ? "" : t;
      this.typeOpen = false;
    },
    submit() {
      const params = new URLSearchParams();
      if (this.destination.trim()) params.set("q", this.destination.trim());
      if (this.date) params.set("date", this.date);
      if (this.type) params.set("type", this.type);
      const qs = params.toString();
      window.location.href = "/one-day-tours" + (qs ? `?${qs}` : "");
    },
  }));

  /* --------------------------------------------------------------------------
     Quick booking request (Home §5) — submitted to the booking CRM; submitting
     just shows the confirmation message from the spec and resets the form.
     ------------------------------------------------------------------------ */
  Alpine.data("quickRequest", () => ({
    sent: false,
    form: { name: "", email: "", phone: "", tour: "", people: "2", message: "" },
    tours: (window.DreamTourData?.tours || []).map((t) => t.name),
    async submit() {
      try { await postRequest({ ...person(this.form.name), type: 'tour', email: this.form.email, phone: this.form.phone, participant_count: Number(this.form.people), comments: `Requested tour: ${this.form.tour}\n${this.form.message || ''}` }); } catch (error) { Alpine.store('toast').push({ message: error.message, type: 'error' }); return; }
      this.sent = true;
      this.form = { name: "", email: "", phone: "", tour: "", people: "2", message: "" };
      Alpine.store("toast").push({
        message:
          "Your request has been received. A DreamTour representative will contact you to confirm your booking.",
        timeout: 6000,
      });
    },
  }));

  /* --------------------------------------------------------------------------
     Visa support enquiry (§17) — logged as a separate request type in the CRM
     and confirms the stored CRM request.
     ------------------------------------------------------------------------ */
  const blankVisaForm = () => ({
    name: "",
    email: "",
    phone: "",
    nationality: "",
    destination: "",
    travelDate: "",
    applicants: "1",
    message: "",
  });
  Alpine.data("visaRequest", () => ({
    sent: false,
    form: blankVisaForm(),
    countries: (window.DreamTourData?.visa?.services || []).map((s) => s.country),
    init() {
      const dest = new URLSearchParams(window.location.search).get("country");
      if (dest && this.countries.includes(dest)) this.form.destination = dest;
    },
    async submit() {
      try { await postRequest({ ...person(this.form.name), type: 'visa', email: this.form.email, phone: this.form.phone, participant_count: Number(this.form.applicants), comments: `Nationality: ${this.form.nationality}\nDestination: ${this.form.destination}\nTravel date: ${this.form.travelDate}\n${this.form.message || ''}` }); } catch (error) { Alpine.store('toast').push({ message: error.message, type: 'error' }); return; }
      this.sent = true;
      this.form = blankVisaForm();
      Alpine.store("toast").push({
        message:
          "Your visa support request has been received. A DreamTour advisor will contact you with your document list and a quote.",
        timeout: 6000,
      });
    },
  }));

  /* --------------------------------------------------------------------------
     Private tour request (§4) — quote enquiry for a custom private trip.
     ------------------------------------------------------------------------ */
  const blankPrivateForm = () => ({
    name: "",
    email: "",
    phone: "",
    groupSize: "",
    occasion: "",
    idea: "",
    dates: "",
    vehicle: "",
    message: "",
  });
  Alpine.data("privateRequest", () => ({
    sent: false,
    form: blankPrivateForm(),
    occasions: ["Family", "Friends", "Corporate / group", "Special occasion", "Other"],
    vehicles: [
      "No preference",
      "Minivan (up to 6)",
      "24-seat minibus",
      "56-seat coach",
    ],
    init() {
      const idea = new URLSearchParams(window.location.search).get("idea");
      if (idea) this.form.idea = idea;
    },
    prefill(title) {
      this.form.idea = title;
      const el = document.getElementById("request");
      if (el) el.scrollIntoView({ behavior: "smooth" });
    },
    async submit() {
      try { await postRequest({ ...person(this.form.name), type: 'private_tour', email: this.form.email, phone: this.form.phone, participant_count: Number(this.form.groupSize || 1), comments: `Idea: ${this.form.idea}\nDates: ${this.form.dates}\nOccasion: ${this.form.occasion}\nVehicle: ${this.form.vehicle}\n${this.form.message || ''}` }); } catch (error) { Alpine.store('toast').push({ message: error.message, type: 'error' }); return; }
      this.sent = true;
      this.form = blankPrivateForm();
      Alpine.store("toast").push({
        message:
          "Thanks — your private tour request has been received. A DreamTour planner will contact you with ideas and a quote.",
        timeout: 6000,
      });
    },
  }));

  /* --------------------------------------------------------------------------
     Contact form (§16) — logged as a new CRM request in production. Phase 1
     just confirms and resets.
     ------------------------------------------------------------------------ */
  const blankContactForm = () => ({
    name: "",
    email: "",
    phone: "",
    pickup: "",
    subject: "",
    message: "",
  });
  Alpine.data("contactForm", () => ({
    sent: false,
    form: blankContactForm(),
    pickupPoints: (window.DreamTourData?.pickupPoints || []).map(
      (p) => `${p.name} — ${p.area}`,
    ),
    async submit() {
      try { await postRequest({ ...person(this.form.name), type: 'contact', email: this.form.email, phone: this.form.phone, participant_count: 1, comments: `Subject: ${this.form.subject}\nPreferred pickup: ${this.form.pickup}\n${this.form.message}` }); } catch (error) { Alpine.store('toast').push({ message: error.message, type: 'error' }); return; }
      this.sent = true;
      this.form = blankContactForm();
      Alpine.store("toast").push({
        message:
          "Thanks — your message has been sent. A DreamTour representative will get back to you shortly.",
        timeout: 6000,
      });
    },
  }));

  /* --------------------------------------------------------------------------
     Favourites — saved tours and cruises, kept in this browser via
     localStorage so they survive reloads and page changes. Tours are stored
     under their plain id, cruises under `cruise-<id>`; everything is coerced
     to a string so both shapes compare the same way.
     ------------------------------------------------------------------------ */
  const FAVOURITES_KEY = "dreamtour.favourites";

  // Storage throws in private mode and when a browser blocks site data, so
  // every read and write degrades to an in-memory list rather than breaking.
  const readFavourites = () => {
    try {
      const stored = JSON.parse(window.localStorage.getItem(FAVOURITES_KEY) || "[]");
      return Array.isArray(stored) ? stored.map(String) : [];
    } catch (error) {
      return [];
    }
  };
  const writeFavourites = (ids) => {
    try {
      window.localStorage.setItem(FAVOURITES_KEY, JSON.stringify(ids));
    } catch (error) {
      /* storage unavailable — favourites last for this page only */
    }
  };

  Alpine.store("wishlist", {
    ids: readFavourites(),
    get count() {
      return this.ids.length;
    },
    has(id) {
      return this.ids.includes(String(id));
    },
    toggle(id, name) {
      const key = String(id);
      if (this.has(key)) {
        this.ids = this.ids.filter((x) => x !== key);
        Alpine.store("toast").push({ message: `Removed “${name}” from your favourites` });
      } else {
        this.ids = [...this.ids, key];
        Alpine.store("toast").push({ message: `Saved “${name}” to your favourites` });
      }
      writeFavourites(this.ids);
    },
    clear() {
      this.ids = [];
      writeFavourites(this.ids);
    },
  });

  // Keep other open tabs in step.
  window.addEventListener("storage", (event) => {
    if (event.key === FAVOURITES_KEY) Alpine.store("wishlist").ids = readFavourites();
  });

  /* --------------------------------------------------------------------------
     Tour card list — shared rendering helpers for tour-card grids.
     ------------------------------------------------------------------------ */
  const badgeClass = (label) =>
    ({
      "New Tour": "bg-emerald-500 text-white",
      Popular: "bg-brand-600 text-white",
      "Limited Seats": "bg-amber-400 text-ink-900",
      "Last Seats": "bg-orange-500 text-white",
      "Sold Out": "bg-ink-700 text-white",
      "Coming Soon": "bg-sky-500 text-white",
      "Special Price": "bg-violet-500 text-white",
    }[label] || "bg-ink-700 text-white");

  const seatsLabel = (t) => {
    if (t.seatsLeft === 0) return "Sold out";
    if (t.seatsLeft <= 5) return `Only ${t.seatsLeft} seats left`;
    return `${t.seatsLeft} seats available`;
  };

  Alpine.data("tourList", (source = "tours") => ({
    tours: window.DreamTourData[source] || [],
    badgeClass,
    seatsLabel,
  }));

  /* --------------------------------------------------------------------------
     Favourites page — resolves the saved ids against the catalogue this page
     was served with. Anything saved that is no longer published simply drops
     out of the list, and is reported so the count still adds up.
     ------------------------------------------------------------------------ */
  Alpine.data("favouritesPage", () => ({
    badgeClass,
    seatsLabel,
    get saved() {
      return Alpine.store("wishlist").ids;
    },
    get tours() {
      return (window.DreamTourData?.tours || []).filter((t) => this.saved.includes(String(t.id)));
    },
    get cruises() {
      return (window.DreamTourData?.cruises || []).filter((c) => this.saved.includes(`cruise-${c.id}`));
    },
    get total() {
      return this.tours.length + this.cruises.length;
    },
    get unavailable() {
      return Math.max(0, this.saved.length - this.total);
    },
    clear() {
      Alpine.store("wishlist").clear();
    },
  }));

  /* --------------------------------------------------------------------------
     Tour listing filter (§5) — client-side filtering / sorting / "load more"
     over the mock catalogue. Pass the scope the page is bound to:
     'one-day' | 'multi-day' (matches t.type) or 'international' (matches
     t.international). e.g. x-data="tourFilter('one-day')".
     ------------------------------------------------------------------------ */
  Alpine.data("tourFilter", (scope = "one-day") => ({
    all: (window.DreamTourData?.tours || []).filter((t) =>
      scope === "international" ? t.international : t.type === scope,
    ),
    q: "",
    date: "",
    regions: [],
    destinations: [],
    vehicles: [],
    durations: [],
    month: "",
    priceMax: 0,
    minSeats: 0,
    age: "",
    availability: "",
    tourKind: "",
    hideSoldOut: false,
    sort: "date",
    perPage: 6,
    shown: 6,
    filtersOpen: false, // mobile filter panel

    init() {
      this.priceMax = this.priceCeiling;
      const p = new URLSearchParams(window.location.search);
      if (p.get("q")) this.q = p.get("q");
      if (p.get("date")) this.date = p.get("date");
      if (p.get("region")) this.regions = [p.get("region")];
      if (p.get("destination")) this.destinations = [p.get("destination")];
      if (p.get("vehicle")) this.vehicles = [p.get("vehicle")];
      if (p.get("duration")) this.durations = [p.get("duration")];
      if (p.get("min_seats")) this.minSeats = Number(p.get("min_seats"));
      if (p.get("age")) this.age = p.get("age");
      if (p.get("availability")) this.availability = p.get("availability");
      if (p.get("kind")) this.tourKind = p.get("kind");
    },

    get priceCeiling() {
      return this.all.reduce((m, t) => Math.max(m, t.price), 0);
    },
    get priceFloor() {
      return this.all.reduce((m, t) => Math.min(m, t.price), Infinity) || 0;
    },
    durationBucket(t) {
      const d = t.days || 1;
      if (d <= 1) return "Day trip";
      if (d <= 3) return "2–3 days";
      if (d <= 5) return "4–5 days";
      return "6+ days";
    },
    get durationOptions() {
      const order = ["2–3 days", "4–5 days", "6+ days"];
      const present = new Set(this.all.map((t) => this.durationBucket(t)));
      return order.filter((o) => present.has(o));
    },
    get months() {
      const seen = new Map();
      this.all.forEach((t) => {
        const d = new Date(t.dateValue);
        seen.set(d.getMonth(), d.toLocaleString("en-US", { month: "long" }));
      });
      return [...seen.keys()].sort((a, b) => a - b).map((k) => seen.get(k));
    },
    get regionOptions() {
      return [...new Set(this.all.map((t) => t.country).filter(Boolean))];
    },
    get destinationOptions() {
      return [...new Set(this.all.map((t) => t.destination).filter(Boolean))];
    },
    get vehicleOptions() {
      return [...new Set(this.all.map((t) => t.vehicle))];
    },
    get ageOptions() {
      return [...new Set(this.all.map((t) => t.ageRestriction).filter(Boolean))];
    },
    get availabilityOptions() {
      return [...new Set(this.all.map((t) => t.availability).filter(Boolean))];
    },

    passes(t) {
      if (this.q) {
        const hay = [t.name,t.destination,t.country,...(t.categories||[])].filter(Boolean).join(" ").toLowerCase();
        if (!hay.includes(this.q.trim().toLowerCase())) return false;
      }
      if (this.date && t.dateValue !== this.date) return false;
      if (this.regions.length && !this.regions.includes(t.country)) return false;
      if (this.destinations.length && !this.destinations.includes(t.destination)) return false;
      if (this.vehicles.length && !this.vehicles.includes(t.vehicle)) return false;
      if (this.durations.length && !this.durations.includes(this.durationBucket(t)))
        return false;
      if (this.month) {
        const m = new Date(t.dateValue).toLocaleString("en-US", { month: "long" });
        if (m !== this.month) return false;
      }
      if (Number(this.priceMax) && t.price > Number(this.priceMax)) return false;
      if (Number(this.minSeats) && t.seatsLeft < Number(this.minSeats)) return false;
      if (this.age && t.ageRestriction !== this.age) return false;
      if (this.availability && t.availability !== this.availability) return false;
      if (this.tourKind === 'private' && !t.isPrivate) return false;
      if (this.tourKind === 'group' && t.isPrivate) return false;
      if (this.tourKind === 'cruise' && !t.isCruise) return false;
      if (this.hideSoldOut && t.seatsLeft === 0) return false;
      return true;
    },

    get filtered() {
      const by = {
        date: (a, b) => new Date(a.dateValue) - new Date(b.dateValue),
        "price-asc": (a, b) => a.price - b.price,
        "price-desc": (a, b) => b.price - a.price,
      }[this.sort];
      return this.all.filter((t) => this.passes(t)).sort(by);
    },
    get results() {
      return this.filtered.slice(0, this.shown);
    },
    get hasMore() {
      return this.filtered.length > this.shown;
    },
    more() {
      this.shown += this.perPage;
    },

    get activeCount() {
      return (
        (this.q ? 1 : 0) +
        (this.date ? 1 : 0) +
        this.regions.length +
        this.destinations.length +
        this.vehicles.length +
        this.durations.length +
        (this.month ? 1 : 0) +
        (Number(this.priceMax) < this.priceCeiling ? 1 : 0) +
        (Number(this.minSeats) ? 1 : 0) +
        (this.age ? 1 : 0) +
        (this.availability ? 1 : 0) +
        (this.tourKind ? 1 : 0) +
        (this.hideSoldOut ? 1 : 0)
      );
    },
    reset() {
      this.q = "";
      this.date = "";
      this.regions = [];
      this.destinations = [];
      this.vehicles = [];
      this.durations = [];
      this.month = "";
      this.priceMax = this.priceCeiling;
      this.minSeats = 0;
      this.age = "";
      this.availability = "";
      this.tourKind = "";
      this.hideSoldOut = false;
      this.sort = "date";
      this.shown = this.perPage;
    },

    badgeClass(label) {
      return (
        {
          "New Tour": "bg-emerald-500 text-white",
          Popular: "bg-brand-600 text-white",
          "Limited Seats": "bg-amber-400 text-ink-900",
          "Last Seats": "bg-orange-500 text-white",
          "Sold Out": "bg-ink-700 text-white",
          "Coming Soon": "bg-sky-500 text-white",
          "Special Price": "bg-violet-500 text-white",
        }[label] || "bg-ink-700 text-white"
      );
    },
    seatsLabel(t) {
      if (t.seatsLeft === 0) return "Sold out";
      if (t.seatsLeft <= 5) return `Only ${t.seatsLeft} seats left`;
      return `${t.seatsLeft} seats available`;
    },
  }));

  /* --------------------------------------------------------------------------
     Cruise listing (§18) — same shape of client-side filtering as tourFilter,
     over the separate `cruises` collection (cruise line, port, cabin types).
     ------------------------------------------------------------------------ */
  Alpine.data("cruiseList", () => ({
    all: window.DreamTourData?.cruises || [],
    q: "",
    date: "",
    regions: [],
    lines: [],
    durations: [],
    month: "",
    priceMax: 0,
    sort: "date",
    perPage: 6,
    shown: 6,
    filtersOpen: false,

    init() {
      this.priceMax = this.priceCeiling;
      const p = new URLSearchParams(window.location.search);
      if (p.get("date")) this.date = p.get("date");
      if (p.get("region")) this.regions = [p.get("region")];
      if (p.get("line")) this.lines = [p.get("line")];
    },

    get priceCeiling() {
      return this.all.reduce((m, c) => Math.max(m, c.price), 0);
    },
    get priceFloor() {
      return this.all.reduce((m, c) => Math.min(m, c.price), Infinity) || 0;
    },
    get regionOptions() {
      return [...new Set(this.all.map((c) => c.region))];
    },
    get lineOptions() {
      return [...new Set(this.all.map((c) => c.line))].sort();
    },
    durationBucket(c) {
      if (c.nights <= 5) return "3–5 nights";
      if (c.nights <= 8) return "6–8 nights";
      return "9+ nights";
    },
    get durationOptions() {
      const order = ["3–5 nights", "6–8 nights", "9+ nights"];
      const present = new Set(this.all.map((c) => this.durationBucket(c)));
      return order.filter((o) => present.has(o));
    },
    get months() {
      const seen = new Map();
      this.all.forEach((c) => {
        const d = new Date(c.date);
        seen.set(d.getMonth(), d.toLocaleString("en-US", { month: "long" }));
      });
      return [...seen.keys()].sort((a, b) => a - b).map((k) => seen.get(k));
    },

    passes(c) {
      if (this.q) {
        const hay = (c.name + " " + c.destination + " " + c.region + " " + c.line + " " + c.port).toLowerCase();
        if (!hay.includes(this.q.trim().toLowerCase())) return false;
      }
      if (this.date && new Date(c.date) < new Date(this.date)) return false;
      if (this.regions.length && !this.regions.includes(c.region)) return false;
      if (this.lines.length && !this.lines.includes(c.line)) return false;
      if (this.durations.length && !this.durations.includes(this.durationBucket(c)))
        return false;
      if (this.month) {
        const m = new Date(c.date).toLocaleString("en-US", { month: "long" });
        if (m !== this.month) return false;
      }
      if (Number(this.priceMax) && c.price > Number(this.priceMax)) return false;
      return true;
    },

    get filtered() {
      const by = {
        date: (a, b) => new Date(a.date) - new Date(b.date),
        "price-asc": (a, b) => a.price - b.price,
        "price-desc": (a, b) => b.price - a.price,
      }[this.sort];
      return this.all.filter((c) => this.passes(c)).sort(by);
    },
    get results() {
      return this.filtered.slice(0, this.shown);
    },
    get hasMore() {
      return this.filtered.length > this.shown;
    },
    more() {
      this.shown += this.perPage;
    },

    get activeCount() {
      return (
        (this.q ? 1 : 0) +
        (this.date ? 1 : 0) +
        this.regions.length +
        this.lines.length +
        this.durations.length +
        (this.month ? 1 : 0) +
        (Number(this.priceMax) < this.priceCeiling ? 1 : 0)
      );
    },
    reset() {
      this.q = "";
      this.date = "";
      this.regions = [];
      this.lines = [];
      this.durations = [];
      this.month = "";
      this.priceMax = this.priceCeiling;
      this.sort = "date";
      this.shown = this.perPage;
    },

    badgeClass(label) {
      return (
        {
          "New Tour": "bg-emerald-500 text-white",
          Popular: "bg-brand-600 text-white",
          "Limited Seats": "bg-amber-400 text-ink-900",
          "Last Seats": "bg-orange-500 text-white",
          "Sold Out": "bg-ink-700 text-white",
          "Coming Soon": "bg-sky-500 text-white",
          "Special Price": "bg-violet-500 text-white",
        }[label] || "bg-ink-700 text-white"
      );
    },
    cabinsLabel(c) {
      if (c.cabinsLeft <= 10) return `Only ${c.cabinsLeft} cabins left`;
      return `${c.cabinsLeft} cabins available`;
    },
  }));

  /* --------------------------------------------------------------------------
     Gallery browser (§14) — filter past-trip albums by tour / country / year /
     month / category, with a keyboard-navigable lightbox.
     ------------------------------------------------------------------------ */
  Alpine.data("galleryBrowser", () => ({
    albums: window.DreamTourData?.galleryAlbums || [],
    q: "",
    country: "",
    year: "",
    month: "",
    category: "",
    perPage: 6,
    shown: 6,
    activeAlbum: null,
    photoIndex: 0,

    init() {
      const p = new URLSearchParams(window.location.search);
      if (p.get("country")) this.country = p.get("country");
      if (p.get("year")) this.year = p.get("year");
      if (p.get("category")) this.category = p.get("category");
      if (p.get("tour")) this.q = p.get("tour");
    },

    get countries() {
      return [...new Set(this.albums.map((a) => a.country))].sort();
    },
    get years() {
      return [...new Set(this.albums.map((a) => a.year))].sort((a, b) => b - a);
    },
    get months() {
      const idx = {
        January: 0, February: 1, March: 2, April: 3, May: 4, June: 5,
        July: 6, August: 7, September: 8, October: 9, November: 10, December: 11,
      };
      return [...new Set(this.albums.map((a) => a.month))].sort(
        (a, b) => idx[a] - idx[b],
      );
    },
    get categories() {
      return [...new Set(this.albums.map((a) => a.category))].sort();
    },
    get totalPhotos() {
      return this.albums.reduce((n, a) => n + a.photos.length, 0);
    },

    passes(a) {
      if (this.q) {
        const hay = (a.tour + " " + a.country + " " + a.category).toLowerCase();
        if (!hay.includes(this.q.trim().toLowerCase())) return false;
      }
      if (this.country && a.country !== this.country) return false;
      if (this.year && String(a.year) !== String(this.year)) return false;
      if (this.month && a.month !== this.month) return false;
      if (this.category && a.category !== this.category) return false;
      return true;
    },
    get filtered() {
      return this.albums
        .filter((a) => this.passes(a))
        .sort((x, y) => new Date(y.date) - new Date(x.date));
    },
    get results() {
      return this.filtered.slice(0, this.shown);
    },
    get hasMore() {
      return this.filtered.length > this.shown;
    },
    more() {
      this.shown += this.perPage;
    },
    get activeCount() {
      return (
        (this.q ? 1 : 0) +
        (this.country ? 1 : 0) +
        (this.year ? 1 : 0) +
        (this.month ? 1 : 0) +
        (this.category ? 1 : 0)
      );
    },
    reset() {
      this.q = this.country = this.year = this.month = this.category = "";
      this.shown = this.perPage;
    },

    /* ---- lightbox ---- */
    open(album, i = 0) {
      this.activeAlbum = album;
      this.photoIndex = i;
      document.body.classList.add("overflow-hidden");
    },
    close() {
      this.activeAlbum = null;
      document.body.classList.remove("overflow-hidden");
    },
    next() {
      if (this.activeAlbum)
        this.photoIndex = (this.photoIndex + 1) % this.activeAlbum.photos.length;
    },
    prev() {
      if (!this.activeAlbum) return;
      const n = this.activeAlbum.photos.length;
      this.photoIndex = (this.photoIndex - 1 + n) % n;
    },
    get currentPhoto() {
      return this.activeAlbum ? this.activeAlbum.photos[this.photoIndex] : null;
    },
  }));

  /* --------------------------------------------------------------------------
     Toast notifications — Alpine.store('toast').push({ ... }).
     ------------------------------------------------------------------------ */
  Alpine.store("toast", {
    items: [],
    push({ message, type = "success", timeout = 3500 }) {
      const id = Date.now() + Math.random();
      this.items.push({ id, message, type });
      setTimeout(() => this.dismiss(id), timeout);
    },
    dismiss(id) {
      this.items = this.items.filter((t) => t.id !== id);
    },
  });
});
