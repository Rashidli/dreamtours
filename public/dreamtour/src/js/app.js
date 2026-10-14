/* ============================================================================
   DreamTour — Alpine.js bootstrap
   ----------------------------------------------------------------------------
   Registers shared stores and reusable Alpine components. This file is loaded
   BEFORE vendor/alpine.min.js, so everything hangs off the `alpine:init` event.

   Page-specific behaviour should be written declaratively in the HTML with
   x-data. Only genuinely reusable logic belongs here.
   ========================================================================== */

/* --------------------------------------------------------------------------
   Cookie consent — the visitor's choice lives in the `dt_consent` cookie
   (12 months). Essential storage is always on; "preferences" (favourites,
   remembered sort order), "analytics" and "marketing" only when allowed.
   ------------------------------------------------------------------------ */
const readConsent = () => {
  try {
    const m = document.cookie.match(/(?:^|; )dt_consent=([^;]*)/);
    return m ? JSON.parse(decodeURIComponent(m[1])) : null;
  } catch (error) {
    return null;
  }
};
const consentAllows = (category) => category === "essential" || !!readConsent()?.[category];
/** Where remembered preferences go: kept between visits only with consent, otherwise for this visit. */
const prefStorage = () => {
  try {
    return consentAllows("preferences") ? window.localStorage : window.sessionStorage;
  } catch (error) {
    return null;
  }
};

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
    full_name: "",
    email: "",
    phone_country: "CA",
    phone: "",
    country: "",
    countryOther: "",
    passport_country: "",
    assistance: [],
    applicants: "1",
    travel_date: "",
    message: "",
  });

  /** Digits of a local number valid for the dialling code, or "" — mirrors international_phone() in helpers.php. */
  const intlPhoneDigits = (dial, number) => {
    let digits = String(number || "").replace(/\D+/g, "");
    if (dial === "1") {
      if (digits.length === 11 && digits[0] === "1") digits = digits.slice(1);
      return digits.length === 10 && digits[0] >= "2" ? digits : "";
    }
    digits = digits.replace(/^0+/, "");
    const length = String(dial || "").length + digits.length;
    return dial && digits.length >= 4 && length >= 8 && length <= 15 ? digits : "";
  };

  /* Searchable dialling-code picker; lives inside a component whose `form` has phone_country. */
  Alpine.data("dialPicker", () => ({
    dialOpen: false,
    dialQuery: "",
    get dials() {
      return window.DreamTourDialCodes || [];
    },
    get dialCountry() {
      return this.dials.find((c) => c.iso === this.form.phone_country) || null;
    },
    get filteredDials() {
      const q = this.dialQuery.trim().toLowerCase().replace(/^\+/, "");
      return q ? this.dials.filter((c) => c.name.toLowerCase().includes(q) || c.dial.startsWith(q) || c.iso.toLowerCase() === q) : this.dials;
    },
    toggleDial() {
      this.dialOpen = !this.dialOpen;
      this.dialQuery = "";
      if (this.dialOpen) this.$nextTick(() => this.$refs.dialSearch?.focus());
    },
    chooseDial(c) {
      if (!c) return;
      this.form.phone_country = c.iso;
      this.dialOpen = false;
    },
  }));

  Alpine.data("visaRequest", () => ({
    sent: false,
    sending: false,
    reference: "",
    error: "",
    form: blankVisaForm(),
    countries: [...new Set((window.DreamTourData?.visa?.services || []).map((s) => s.country))],
    init() {
      const dest = new URLSearchParams(window.location.search).get("country");
      if (dest && this.countries.includes(dest)) this.form.country = dest;
    },
    validate() {
      const f = this.form;
      const dial = (window.DreamTourDialCodes || []).find((c) => c.iso === f.phone_country)?.dial;
      if (!f.full_name.trim()) return "Please enter your full name.";
      if (!/^\S+@\S+\.\S+$/.test(f.email)) return "Please enter a valid email address.";
      if (!intlPhoneDigits(dial, f.phone)) return "Please enter a complete phone or WhatsApp number for the selected country.";
      if (!f.country || (f.country === "Other" && !f.countryOther.trim())) return "Please choose the country you need a visa for.";
      if (!f.assistance.length) return "Please choose what you need help with.";
      return "";
    },
    async submit() {
      if (this.sending) return;
      this.error = this.validate();
      if (this.error) return;
      const { countryOther, ...form } = this.form;
      const payload = { ...form, country: form.country === "Other" ? countryOther.trim() : form.country, applicants: Number(form.applicants) || 1 };
      if (!payload.travel_date) delete payload.travel_date;
      this.sending = true;
      try {
        const response = await fetch("/visa-enquiries", {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "" },
          body: JSON.stringify(payload),
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(body.errors || {}).flat()[0] || body.message || "Your enquiry could not be sent.");
        this.reference = body.reference || "";
        this.sent = true;
        this.form = blankVisaForm();
        this.$el.closest("section")?.scrollIntoView({ behavior: "smooth", block: "start" });
      } catch (e) {
        this.error = e.message;
      } finally {
        this.sending = false;
      }
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
      const stored = JSON.parse(prefStorage()?.getItem(FAVOURITES_KEY) || "[]");
      return Array.isArray(stored) ? stored.map(String) : [];
    } catch (error) {
      return [];
    }
  };
  const writeFavourites = (ids) => {
    try {
      prefStorage()?.setItem(FAVOURITES_KEY, JSON.stringify(ids));
    } catch (error) {
      /* storage unavailable — favourites last for this page only */
    }
  };

  Alpine.store("consent", {
    decided: false,
    preferences: false,
    analytics: false,
    marketing: false,
    panel: false,
    init() {
      const saved = readConsent();
      this.decided = !!saved;
      this.preferences = !!saved?.preferences;
      this.analytics = !!saved?.analytics;
      this.marketing = !!saved?.marketing;
      this.apply();
    },
    openPanel() {
      this.panel = true;
    },
    acceptAll() {
      this.preferences = this.analytics = this.marketing = true;
      this.save();
    },
    rejectOptional() {
      this.preferences = this.analytics = this.marketing = false;
      this.save();
    },
    save() {
      const hadPrefs = consentAllows("preferences");
      const value = { v: 1, preferences: this.preferences, analytics: this.analytics, marketing: this.marketing, at: new Date().toISOString() };
      document.cookie = `dt_consent=${encodeURIComponent(JSON.stringify(value))}; Max-Age=31536000; Path=/; SameSite=Lax${location.protocol === "https:" ? "; Secure" : ""}`;
      // Move remembered favourites to where the new choice keeps them.
      if (hadPrefs !== this.preferences) {
        try {
          const ids = JSON.stringify(Alpine.store("wishlist").ids);
          (this.preferences ? window.localStorage : window.sessionStorage).setItem(FAVOURITES_KEY, ids);
          if (!this.preferences) {
            window.localStorage.removeItem(FAVOURITES_KEY);
            Object.keys(window.localStorage).filter((k) => k.startsWith("dreamtour.")).forEach((k) => window.localStorage.removeItem(k));
          }
        } catch (error) {
          /* storage unavailable */
        }
      }
      if (!this.analytics || !this.marketing) {
        // Withdrawn consent: drop analytics / ad cookies already set on this domain.
        document.cookie.split("; ").map((c) => c.split("=")[0]).filter((n) => (!this.analytics && /^(_ga|_gid|_gat)/.test(n)) || (!this.marketing && /^(_fbp|_fbc)/.test(n)))
          .forEach((n) => {
            // Trackers often set their cookie on the parent domain, so clear it on every level.
            const parts = location.hostname.split(".");
            document.cookie = `${n}=; Max-Age=0; Path=/`;
            for (let i = 0; i < parts.length - 1; i++) document.cookie = `${n}=; Max-Age=0; Path=/; Domain=.${parts.slice(i).join(".")}`;
          });
      }
      this.decided = true;
      this.panel = false;
      this.apply();
      Alpine.store("toast").push({ message: "Your cookie choices are saved." });
    },
    /** Load third-party tags only for the categories the visitor allowed. */
    apply() {
      const cfg = window.DreamTourConsentConfig || {};
      const load = (id, src, inline) => {
        if (document.getElementById(id)) return;
        if (src) { const s = document.createElement("script"); s.async = true; s.src = src; s.id = id; document.head.appendChild(s); }
        if (inline) { const s = document.createElement("script"); s.id = id + "-init"; s.textContent = inline; document.head.appendChild(s); }
      };
      if (this.analytics && cfg.ga) {
        load("dt-ga", `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(cfg.ga)}`, `window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',${JSON.stringify(cfg.ga)});`);
      }
      if (this.analytics && cfg.gtm) {
        load("dt-gtm", null, `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',${JSON.stringify(cfg.gtm)});`);
      }
      if (this.marketing && cfg.pixel) {
        load("dt-pixel", null, `!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',${JSON.stringify(cfg.pixel)});fbq('track','PageView');`);
      }
    },
  });

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
    all: (window.DreamTourData?.tours || []).filter((t) => (t.listings || [t.type]).includes(scope)),
    categoryId: null,
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
    picked: [], // ids of staff-managed filter options (admin: Tour filters)
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
      if (p.get("destination")) {
        // Links carry the destination id; the filter works on names.
        const id = Number(p.get("destination"));
        const match = this.all.find((t) => t.destinationId === id);
        const country = match ? null : this.all.find((t) => t.countryId === id);
        if (country) this.regions = [country.country];
        else this.destinations = [match ? match.destination : p.get("destination")];
      }
      if (p.get("category")) this.categoryId = Number(p.get("category"));
      if (p.get("vehicle")) this.vehicles = [p.get("vehicle")];
      if (p.get("duration")) this.durations = [p.get("duration")];
      if (p.get("min_seats")) this.minSeats = Number(p.get("min_seats"));
      if (p.get("age")) this.age = p.get("age");
      if (p.get("availability")) this.availability = p.get("availability");
      if (p.get("kind")) this.tourKind = p.get("kind");
      if (p.get("f")) this.picked = p.get("f").split(",").map(Number).filter(Boolean);
      // Remember the visitor's sort order (between visits only with preference cookies allowed).
      const sortKey = `dreamtour.sort.${scope}`;
      try {
        const savedSort = prefStorage()?.getItem(sortKey);
        if (savedSort && ["date", "price-asc", "price-desc"].includes(savedSort)) this.sort = savedSort;
      } catch (error) {
        /* storage unavailable */
      }
      this.$watch("sort", (value) => {
        try {
          prefStorage()?.setItem(sortKey, value);
        } catch (error) {
          /* storage unavailable */
        }
      });
    },

    /** Admin-defined filter groups for this listing, showing only options some tour here uses. */
    get customFilters() {
      const used = new Set(this.all.flatMap((t) => t.filterOptionIds || []));
      return (window.DreamTourData?.tourFilters || [])
        .filter((f) => f.listing === "both" || f.listing === scope)
        .map((f) => ({ ...f, options: f.options.filter((o) => used.has(o.id)) }))
        .filter((f) => f.options.length);
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
        (t.dates || []).forEach((value) => {
          const d = new Date(value + "T12:00:00");
          seen.set(d.getFullYear() * 12 + d.getMonth(), d.toLocaleString("en-US", { month: "long" }));
        });
      });
      return [...new Set([...seen.keys()].sort((a, b) => a - b).map((k) => seen.get(k)))];
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
      if (this.categoryId && !(t.categoryIds || []).includes(this.categoryId)) return false;
      if (this.date && !(t.dates || []).includes(this.date)) return false;
      if (this.regions.length && !this.regions.includes(t.country)) return false;
      if (this.destinations.length && !this.destinations.includes(t.destination)) return false;
      if (this.vehicles.length && !this.vehicles.includes(t.vehicle)) return false;
      if (this.durations.length && !this.durations.includes(this.durationBucket(t)))
        return false;
      if (this.month) {
        const inMonth = (t.dates || []).some(
          (value) => new Date(value + "T12:00:00").toLocaleString("en-US", { month: "long" }) === this.month,
        );
        if (!inMonth) return false;
      }
      if (Number(this.priceMax) && t.price > Number(this.priceMax)) return false;
      if (Number(this.minSeats) && t.seatsLeft < Number(this.minSeats)) return false;
      if (this.age && t.ageRestriction !== this.age) return false;
      if (this.availability && t.availability !== this.availability) return false;
      if (this.tourKind === 'private' && !t.isPrivate) return false;
      if (this.tourKind === 'group' && t.isPrivate) return false;
      if (this.tourKind === 'cruise' && !t.isCruise) return false;
      if (this.hideSoldOut && t.seatsLeft === 0) return false;
      // Within a group any ticked option matches; every group with a tick must match.
      for (const f of this.customFilters) {
        const ticked = f.options.map((o) => o.id).filter((id) => this.picked.includes(id));
        if (ticked.length && !ticked.some((id) => (t.filterOptionIds || []).includes(id))) return false;
      }
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
    /* The cards are rendered server-side so they are in the HTML for crawlers; these two let
       the same filter state show/hide and re-order those existing nodes instead of building
       them from scratch. */
    get visibleIds() {
      return this.results.map((item) => String(item.id));
    },
    orderOf(id) {
      const at = this.results.findIndex((item) => String(item.id) === String(id));
      return at === -1 ? 0 : at;
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
        (this.categoryId ? 1 : 0) +
        (this.hideSoldOut ? 1 : 0) +
        this.picked.length
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
      this.categoryId = null;
      this.picked = [];
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
    /* The cards are rendered server-side so they are in the HTML for crawlers; these two let
       the same filter state show/hide and re-order those existing nodes instead of building
       them from scratch. */
    get visibleIds() {
      return this.results.map((item) => String(item.id));
    },
    orderOf(id) {
      const at = this.results.findIndex((item) => String(item.id) === String(id));
      return at === -1 ? 0 : at;
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
    /* The cards are rendered server-side so they are in the HTML for crawlers; these two let
       the same filter state show/hide and re-order those existing nodes instead of building
       them from scratch. */
    get visibleIds() {
      return this.results.map((item) => String(item.id));
    },
    orderOf(id) {
      const at = this.results.findIndex((item) => String(item.id) === String(id));
      return at === -1 ? 0 : at;
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
    /* The album cards are rendered server-side, so the lightbox is opened by id rather than
       by passing the object the template used to hold. */
    openById(id) {
      const album = this.albums.find((a) => String(a.id) === String(id));
      if (album) this.open(album);
    },
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
     Image carousel — home hero slides and the tour page gallery.
     x-data="carousel({ count: 5, interval: 6000 })"
     ------------------------------------------------------------------------ */
  /* --------------------------------------------------------------------------
     Floating WhatsApp button — hidden while an element marked
     [data-hide-wa-float] is on screen, so it never covers the home search.
     ------------------------------------------------------------------------ */
  Alpine.data("waFloat", () => ({
    hidden: false,
    init() {
      const targets = document.querySelectorAll("[data-hide-wa-float]");
      if (!targets.length || !("IntersectionObserver" in window)) return;
      const onScreen = new Set();
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((e) => (e.isIntersecting ? onScreen.add(e.target) : onScreen.delete(e.target)));
        this.hidden = onScreen.size > 0;
      });
      targets.forEach((t) => observer.observe(t));
    },
  }));

  /* --------------------------------------------------------------------------
     Departure calendar — month grid of a tour's dates. Bookable dates are
     selectable; sold-out / closed dates are shown but disabled. Emits
     `departure-selected` (detail = departure id) to the surrounding component.
     departures: [{ id, date: "YYYY-MM-DD", label, status, statusLabel, bookable, seats, priceLabel }]
     ------------------------------------------------------------------------ */
  Alpine.data("departureCalendar", ({ departures = [], selected = null } = {}) => ({
    departures,
    selected: selected ? Number(selected) : null,
    month: null,
    weekdays: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
    init() {
      const first = this.byId(this.selected) || this.departures.find((d) => d.bookable) || this.departures[0];
      this.month = first ? first.date.slice(0, 7) : new Date().toISOString().slice(0, 7);
      this.$watch("selected", (id) => {
        const d = this.byId(id);
        if (d && d.date.slice(0, 7) !== this.month) this.month = d.date.slice(0, 7);
      });
    },
    byId(id) {
      return this.departures.find((d) => d.id === Number(id)) || null;
    },
    get months() {
      return [...new Set(this.departures.map((d) => d.date.slice(0, 7)))].sort();
    },
    get monthLabel() {
      const [y, m] = this.month.split("-").map(Number);
      return new Date(y, m - 1, 1).toLocaleDateString("en-CA", { month: "long", year: "numeric" });
    },
    get canPrev() {
      return this.months.some((m) => m < this.month);
    },
    get canNext() {
      return this.months.some((m) => m > this.month);
    },
    shift(dir) {
      const list = dir < 0 ? this.months.filter((m) => m < this.month).reverse() : this.months.filter((m) => m > this.month);
      if (list.length) this.month = list[0];
    },
    get cells() {
      const [y, m] = this.month.split("-").map(Number);
      const start = new Date(y, m - 1, 1).getDay();
      const days = new Date(y, m, 0).getDate();
      const cells = [];
      for (let i = 0; i < start; i++) cells.push(null);
      for (let d = 1; d <= days; d++) {
        const date = `${this.month}-${String(d).padStart(2, "0")}`;
        cells.push({ day: d, date, departures: this.departures.filter((x) => x.date === date) });
      }
      return cells;
    },
    get monthDepartures() {
      return this.departures.filter((d) => d.date.startsWith(this.month));
    },
    choose(d) {
      if (!d || !d.bookable) return;
      this.selected = d.id;
      this.$dispatch("departure-selected", d.id);
    },
  }));

  Alpine.data("carousel", ({ count = 0, interval = 0 } = {}) => ({
    index: 0,
    count,
    timer: null,
    touchX: null,
    // Set by the visitor's pause button; hover and focus only pause until they leave.
    stopped: false,
    init() {
      this.play();
    },
    play() {
      if (!interval || this.count < 2 || window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
      clearInterval(this.timer);
      this.timer = setInterval(() => this.next(), interval);
    },
    pause() {
      clearInterval(this.timer);
    },
    resume() {
      if (!this.stopped) this.play();
    },
    toggle() {
      this.stopped = !this.stopped;
      this.stopped ? this.pause() : this.play();
    },
    go(i) {
      this.index = (i + this.count) % this.count;
    },
    next() {
      this.go(this.index + 1);
    },
    prev() {
      this.go(this.index - 1);
    },
    touchStart(e) {
      this.touchX = e.touches[0].clientX;
      this.pause();
    },
    touchEnd(e) {
      if (this.touchX === null) return;
      const dx = e.changedTouches[0].clientX - this.touchX;
      if (Math.abs(dx) > 40) dx < 0 ? this.next() : this.prev();
      this.touchX = null;
      this.resume();
    },
  }));

  /* --------------------------------------------------------------------------
     Tour page request dialog — "Invoice request", "Let us call you" and private
     tour quotes. Every request lands in the admin CRM with its own type.
     ------------------------------------------------------------------------ */
  Alpine.data("tourRequest", ({ tourId, tourName }) => ({
    open: false,
    mode: "invoice",
    sending: false,
    sent: false,
    error: "",
    form: { name: "", phone: "", email: "", people: 2, date: "", time: "", message: "" },
    titles: {
      invoice: "Request an invoice",
      callback: "Let us call you",
      private_tour: "Request a private tour quote",
    },
    show(mode) {
      this.mode = mode;
      this.sent = false;
      this.error = "";
      this.open = true;
      this.$nextTick(() => this.$refs.firstField?.focus());
    },
    async submit() {
      this.error = "";
      if (!this.form.name.trim() || !this.form.phone.trim() || !this.form.email.trim()) {
        this.error = "Please enter your name, phone and email.";
        return;
      }
      this.sending = true;
      const lines = [
        `Tour: ${tourName}`,
        this.form.date ? `Preferred date: ${this.form.date}` : "",
        this.mode === "callback" && this.form.time ? `Best time to call: ${this.form.time}` : "",
        this.form.message,
      ].filter(Boolean);
      try {
        await postRequest({
          ...person(this.form.name),
          type: this.mode,
          tour_id: tourId,
          email: this.form.email,
          phone: this.form.phone,
          participant_count: Math.max(1, Number(this.form.people) || 1),
          comments: lines.join("\n"),
        });
        this.sent = true;
      } catch (error) {
        this.error = error.message;
      } finally {
        this.sending = false;
      }
    },
  }));

  /* --------------------------------------------------------------------------
     Step-by-step tour booking (tour-booking.blade.php). Mirrors the server-side
     App\Support\BookingQuote arithmetic for the live price; the server recalculates.
     ------------------------------------------------------------------------ */
  Alpine.data("bookingWizard", ({ departures = [], old = {}, errors = {}, depositPerPerson = null, taxNote = "", countries = [], pickups = [], meals = [], hasWaiver = false }) => ({
    departures,
    countries,
    pickups,
    meals,
    hasWaiver,
    step: 1,
    steps: ["Date & travellers", "Passengers", "Contact", "Extras", "Payment", "Review"],
    departureId: Number(old.tour_departure_id) || departures[0]?.id || null,
    selections: {},
    passengers: [],
    samePickup: old.passengers ? old.same_pickup === "1" : true,
    phone: old.phone || "",
    waCountry: old.whatsapp_country || "CA",
    waNumber: old.whatsapp || "",
    waSame: old.whatsapp_same === "1",
    waOpen: false,
    waQuery: "",
    email: old.email || "",
    emergencyName: old.emergency_contact_name || "",
    emergencyPhone: old.emergency_contact_phone || "",
    roommate: old.roommate_request || "",
    fileName: "",
    acceptTerms: old.accept_terms === "1",
    acceptPrivacy: old.accept_privacy === "1",
    acceptWaiver: old.accept_waiver === "1",
    marketing: old.marketing_consent === "1",
    submitting: false,
    clientError: "",
    taxNote,

    init() {
      const saved = old.selections || {};
      this.resetSelections(saved);
      (old.passengers || []).forEach((p, i) => {
        if (this.passengers[i]) Object.assign(this.passengers[i], { ...p, is_child: p.is_child === "1" || p.is_child === true });
      });
      // Jump to the first step that has a server-side error.
      const keys = Object.keys(errors);
      const stepOf = (key) => {
        if (/^(tour_departure_id|selections)/.test(key)) return 1;
        if (/^(passengers|roommate)/.test(key)) return 2;
        if (/^(phone|whatsapp|email|emergency)/.test(key)) return 3;
        if (/^(meal|special|food|promo)/.test(key)) return 4;
        return 5;
      };
      if (keys.length) this.step = Math.min(...keys.map(stepOf));
      this.$watch("departureId", () => {
        this.resetSelections({});
        this.fitPickups();
      });
      this.fitPickups();
      this.$watch("phone", () => this.waSame && this.syncWhatsapp());
    },

    get departure() {
      return this.departures.find((d) => d.id === Number(this.departureId)) || null;
    },
    /** Pickup points offered on the selected date. */
    get departurePickups() {
      const ids = this.departure?.pickups || [];
      return this.pickups.filter((p) => ids.includes(p.id));
    },
    pickupName(id) {
      const p = this.pickups.find((x) => x.id === String(id));
      return p ? p.name : "—";
    },
    /** Drop pickup choices the new date does not offer; a date with one point fills it in. */
    fitPickups() {
      const ids = this.departurePickups.map((p) => p.id);
      this.passengers.forEach((p) => {
        if (!ids.includes(String(p.pickup_point_id))) p.pickup_point_id = ids.length === 1 ? ids[0] : "";
      });
    },
    get extrasSummary() {
      const form = this.$root.querySelector("form");
      const val = (name) => form?.querySelector(`[name="${name}"]`)?.value?.trim() || "";
      return [
        ["Meal preference", val("meal_selection")],
        ["Food allergies", val("food_allergies")],
        ["Special requests", val("special_requests")],
        ["Promo code", val("promo_code")],
        ["Roommate requests", this.roommate.trim()],
      ].filter(([, v]) => v).map(([k, v]) => `${k}: ${v}`).join("\n");
    },
    get prices() {
      const d = this.departure;
      if (!d) return [];
      return d.prices.length ? d.prices : [{ code: "adult", label: "Adult", price: d.basePrice, occupancy: null }];
    },
    get roomPricing() {
      return this.prices.some((p) => p.occupancy);
    },
    get childPrice() {
      return this.prices.find((p) => p.code === "child") || null;
    },
    get options() {
      // With room pricing, children are flagged per passenger rather than counted here.
      return this.roomPricing ? this.prices.filter((p) => p.occupancy) : this.prices;
    },
    resetSelections(saved) {
      const next = {};
      this.options.forEach((p, i) => {
        const fallback = this.roomPricing ? 0 : i === 0 ? 1 : 0;
        next[p.code] = saved[p.code] !== undefined ? Number(saved[p.code]) : fallback;
      });
      this.selections = next;
      this.syncPassengers();
    },
    change(code, delta) {
      const value = Math.max(0, Math.min(20, (Number(this.selections[code]) || 0) + delta));
      this.selections[code] = value;
      this.syncPassengers();
    },
    get slots() {
      const slots = [];
      let room = 0;
      this.options.forEach((p) => {
        const qty = Number(this.selections[p.code]) || 0;
        for (let n = 0; n < qty; n++) {
          const roomNo = p.occupancy ? ++room : null;
          for (let s = 0; s < (p.occupancy || 1); s++) slots.push({ code: p.code, label: p.label, price: p.price, room: roomNo, roomLabel: p.label });
        }
      });
      return slots;
    },
    syncPassengers() {
      const count = this.slots.length;
      const only = this.departurePickups.length === 1 ? this.departurePickups[0].id : "";
      const pickup = only || (this.samePickup && this.passengers[0]?.pickup_point_id) || "";
      while (this.passengers.length < count) this.passengers.push({ first_name: "", last_name: "", date_of_birth: "", is_child: false, pickup_point_id: pickup, meal_selection: "" });
      this.passengers = this.passengers.slice(0, count);
    },
    priceOf(i) {
      const slot = this.slots[i];
      if (!slot) return 0;
      if (this.roomPricing && this.childPrice && this.passengers[i]?.is_child) return this.childPrice.price;
      return slot.price;
    },
    fareLabel(i) {
      const slot = this.slots[i];
      if (!slot) return "";
      if (this.roomPricing && this.childPrice && this.passengers[i]?.is_child) return this.childPrice.label;
      return slot.label;
    },
    get count() {
      return this.slots.length;
    },
    get total() {
      return this.slots.reduce((sum, _s, i) => sum + this.priceOf(i), 0);
    },
    get deposit() {
      return depositPerPerson ? Math.min(this.total, depositPerPerson * this.count) : this.total;
    },
    money(value) {
      const currency = this.departure?.currency || "CAD";
      const symbol = { CAD: "CA$", USD: "US$" }[currency] || currency + " ";
      const n = Number(value) || 0;
      return symbol + n.toLocaleString("en-CA", { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 });
    },
    pickupChanged(i) {
      const value = this.passengers[i].pickup_point_id;
      if (!this.samePickup) return;
      if (i === 0) this.passengers.forEach((p) => (p.pickup_point_id = value));
      else if (value !== this.passengers[0].pickup_point_id) this.samePickup = false;
    },
    applySamePickup() {
      if (this.samePickup) this.pickupChanged(0);
    },

    // WhatsApp number with an international dialling code.
    get country() {
      return this.countries.find((c) => c.iso === this.waCountry) || null;
    },
    get filteredCountries() {
      const q = this.waQuery.trim().toLowerCase().replace(/^\+/, "");
      if (!q) return this.countries;
      return this.countries.filter((c) => c.name.toLowerCase().includes(q) || c.dial.startsWith(q) || c.iso.toLowerCase() === q);
    },
    toggleCountries() {
      this.waOpen = !this.waOpen;
      this.waQuery = "";
      // The panel is shown with a class binding (not x-show, which reveals a frame later), so it is visible by the next tick.
      if (this.waOpen) this.$nextTick(() => this.$refs.waSearch?.focus());
    },
    chooseCountry(c) {
      if (!c) return;
      this.waCountry = c.iso;
      this.waOpen = false;
    },
    syncWhatsapp() {
      if (!this.waSame) return;
      this.waCountry = "CA";
      this.waNumber = this.phone;
    },
    // Mirrors international_phone() in app/Helpers/helpers.php.
    whatsappDigits() {
      const dial = this.country?.dial || "";
      let digits = String(this.waNumber || "").replace(/\D+/g, "");
      if (dial === "1") {
        if (digits.length === 11 && digits[0] === "1") digits = digits.slice(1);
        return digits.length === 10 && digits[0] >= "2" ? digits : "";
      }
      digits = digits.replace(/^0+/, "");
      const length = dial.length + digits.length;
      return dial && digits.length >= 4 && length >= 8 && length <= 15 ? digits : "";
    },
    validWhatsapp() {
      return this.whatsappDigits() !== "";
    },
    whatsappPreview() {
      const d = this.whatsappDigits();
      if (this.country?.dial === "1") return `+1 ${d.slice(0, 3)}-${d.slice(3, 6)}-${d.slice(6)}`;
      return `+${this.country?.dial} ${d}`;
    },
    isNewRoom(i) {
      const slot = this.slots[i];
      return slot && slot.room && (i === 0 || this.slots[i - 1].room !== slot.room);
    },

    validPhone(value) {
      let digits = String(value || "").replace(/\D+/g, "");
      if (digits.length === 11 && digits[0] === "1") digits = digits.slice(1);
      return digits.length === 10 && digits[0] >= "2";
    },
    check(step) {
      if (step === 1) {
        if (!this.departure) return "Please choose a departure date.";
        if (!this.count) return this.roomPricing ? "Please choose at least one room." : "Please choose at least one passenger.";
        if (this.count > this.departure.seats) return `Only ${this.departure.seats} seats are left on this date.`;
      }
      if (step === 2) {
        if (this.passengers.some((p) => !p.first_name.trim() || !p.last_name.trim())) return "Please enter the first and last name of every passenger.";
        if (!this.passengers[0]?.date_of_birth) return "Please enter the date of birth of the main passenger.";
        const missing = this.passengers.findIndex((p) => !p.pickup_point_id);
        if (missing !== -1) return `Please choose a pickup point for passenger ${missing + 1}.`;
        const noMeal = this.meals.length ? this.passengers.findIndex((p) => !p.meal_selection) : -1;
        if (noMeal !== -1) return `Please choose a meal for passenger ${noMeal + 1}.`;
      }
      if (step === 3) {
        if (!this.validPhone(this.phone)) return "Please enter a valid Canadian phone number, e.g. 416-555-0100.";
        if (!this.waNumber.trim()) return "Please enter your WhatsApp number.";
        if (!this.validWhatsapp()) return `Please enter a complete WhatsApp number for ${this.country?.name || "the selected country"} (+${this.country?.dial || ""}).`;
        if (!/^\S+@\S+\.\S+$/.test(this.email)) return "Please enter an active email address.";
        if (!this.emergencyName.trim() || !this.emergencyPhone.trim()) return "Please enter an emergency contact name and phone number.";
      }
      if (step === 5) {
        if (!this.fileName) return "Please upload a screenshot of your deposit payment.";
        if (!this.acceptTerms) return "Please accept the Terms and Conditions of Booking.";
        if (!this.acceptPrivacy) return "Please confirm you have read the Privacy Policy.";
        if (this.hasWaiver && !this.acceptWaiver) return "Please read and accept the waiver for this trip.";
      }
      return "";
    },
    next() {
      this.clientError = this.check(this.step);
      if (this.clientError) return;
      this.step = Math.min(this.steps.length, this.step + 1);
      this.$nextTick(() => document.getElementById("booking-steps")?.scrollIntoView({ behavior: "smooth", block: "start" }));
    },
    back() {
      this.clientError = "";
      this.step = Math.max(1, this.step - 1);
    },
    goTo(target) {
      for (let s = 1; s < target; s++) {
        const error = this.check(s);
        if (error) {
          this.step = s;
          this.clientError = error;
          return;
        }
      }
      this.clientError = "";
      this.step = target;
    },
    submit(event) {
      // Enter in a field must not skip the review; and a booking is sent once only.
      if (this.submitting) return event.preventDefault();
      if (this.step < this.steps.length) {
        event.preventDefault();
        return this.next();
      }
      for (let s = 1; s <= this.steps.length; s++) {
        const error = this.check(s);
        if (error) {
          event.preventDefault();
          this.step = s;
          this.clientError = error;
          return;
        }
      }
      this.submitting = true;
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
