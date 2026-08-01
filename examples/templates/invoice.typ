// German commercial invoice layout inspired by Shopware document templates
// (letter header, line items, tax summary, payment/shipping, multi-column footer).

#let data = json(bytes(sys.inputs.at("invoice")))

#let money(n) = {
  let s = str(calc.round(n * 100) / 100)
  // ensure two decimal places
  if not s.contains(".") {
    s = s + ".00"
  } else {
    let parts = s.split(".")
    if parts.at(1).len() == 1 {
      s = s + "0"
    }
  }
  let parts = s.split(".")
  let whole = parts.at(0)
  let frac = parts.at(1)
  // German thousands separators (simple for demo amounts)
  let formatted = whole + "," + frac
  formatted + " " + data.currency
}

#set page(
  paper: "a4",
  margin: (top: 1.8cm, bottom: 2.8cm, left: 1.8cm, right: 1.8cm),
  footer: context {
    set text(size: 7pt, fill: rgb("#444"))
    line(length: 100%, stroke: 0.4pt + rgb("#ccc"))
    v(3pt)
    grid(
      columns: (1.3fr, 1.5fr, 1.1fr, 1fr, auto),
      column-gutter: 6pt,
      [
        #text(weight: "bold")[#data.seller.name] \
        #if data.seller.taxNumber != none [Steuernr.: #data.seller.taxNumber \ ]
        #if data.seller.vatId != none [USt-IdNr.: #data.seller.vatId \ ]
        #if data.seller.taxOffice != none [Finanzamt #data.seller.taxOffice]
      ],
      [
        #text(weight: "bold")[Bankverbindung] \
        #data.seller.bank.name \
        IBAN: #data.seller.bank.iban \
        BIC: #data.seller.bank.bic
      ],
      [
        #if data.seller.placeOfJurisdiction != none [Gerichtsstand: #data.seller.placeOfJurisdiction \ ]
        #if data.seller.placeOfFulfillment != none [Erfüllungsort: #data.seller.placeOfFulfillment]
      ],
      [
        #if data.seller.ceo != none [
          #text(weight: "bold")[Geschäftsführer] \
          #data.seller.ceo
        ]
      ],
      align(right)[Seite #counter(page).display()],
    )
  },
)

#set text(font: "DejaVu Sans", size: 9.5pt)
#set par(leading: 0.55em)

// --- Logo / company mark ---
#align(left)[
  #text(size: 16pt, weight: "bold", fill: rgb("#1a365d"))[#data.seller.name]
  #v(-4pt)
  #text(size: 8pt, fill: rgb("#666"))[Online-Shop · Rechnung]
]

#v(0.8cm)

// --- Letter header: recipient (left) + sender meta (right) ---
#grid(
  columns: (1fr, 1fr),
  column-gutter: 1.2cm,
  [
    #text(size: 7pt, fill: rgb("#555"))[
      #data.seller.addressLine
    ]
    #v(6pt)
    #if data.buyer.company != none [
      *#data.buyer.company* \
    ]
    #if data.buyer.department != none [
      #data.buyer.department \
    ]
    #data.buyer.firstName #data.buyer.lastName \
    #data.buyer.street \
    #data.buyer.zip #data.buyer.city \
    #data.buyer.country
    #if data.buyer.vatId != none [
      \
      USt-IdNr.: #data.buyer.vatId
    ]
  ],
  align(right)[
    #table(
      columns: (auto, auto),
      inset: (x: 0pt, y: 2.5pt),
      stroke: none,
      align: (right, left),
      [*#data.seller.name*], [],
      [#data.seller.street], [],
      [#data.seller.zip #data.seller.city], [],
      [Tel.: #data.seller.phone], [],
      [#data.seller.email], [],
      [#data.seller.url], [],
      [], [],
      [Kundennummer:], [#data.meta.customerNumber],
      [Bestellnummer:], [#data.meta.orderNumber],
      [Bestelldatum:], [#data.meta.orderDate],
      [Rechnungsdatum:], [#data.meta.invoiceDate],
    )
  ],
)

#v(0.7cm)

// --- Headline ---
#text(size: 15pt, weight: "bold")[Rechnung Nr. #data.meta.invoiceNumber]

#v(0.45cm)

// Optional divergent shipping address
#if data.shipping != none and data.shipping.different [
  #text(weight: "bold")[Lieferadresse]
  #v(2pt)
  #if data.shipping.company != none [#data.shipping.company \ ]
  #data.shipping.firstName #data.shipping.lastName \
  #data.shipping.street \
  #data.shipping.zip #data.shipping.city \
  #data.shipping.country
  #v(0.4cm)
]

// --- Line items ---
#let header-cell(body) = table.cell(
  fill: rgb("#f3f4f6"),
  inset: (x: 5pt, y: 6pt),
  text(size: 8pt, weight: "bold")[#body],
)

#table(
  columns: (0.55fr, 1.1fr, 2.6fr, 0.7fr, 0.7fr, 1.1fr, 1.1fr),
  stroke: (x: none, y: 0.4pt + rgb("#ddd")),
  inset: (x: 5pt, y: 5pt),
  align: (left, left, left, right, right, right, right),
  header-cell[Pos.],
  header-cell[Artikel-Nr.],
  header-cell[Bezeichnung],
  header-cell[Menge],
  header-cell[MwSt.],
  header-cell[Einzelpreis\ #text(size: 6.5pt, weight: "regular")[(inkl. MwSt.)]],
  header-cell[Gesamt\ #text(size: 6.5pt, weight: "regular")[(inkl. MwSt.)]],
  ..for (i, item) in data.lineItems.enumerate() {
    (
      str(i + 1),
      item.productNumber,
      {
        item.label
        if item.options != none and item.options != "" {
          [\ #text(size: 7.5pt, fill: rgb("#555"))[#item.options]]
        }
      },
      str(item.quantity),
      str(item.taxRate) + " %",
      money(item.unitPrice),
      money(item.totalPrice),
    )
  },
)

#v(0.5cm)

// --- Totals (right-aligned like Shopware sum-table) ---
#align(right)[
  #table(
    columns: (auto, auto),
    inset: (x: 8pt, y: 3pt),
    stroke: none,
    align: (right, right),
    [Nettosumme], [#money(data.totals.net)],
    ..for tax in data.totals.taxes {
      ([zzgl. #str(tax.rate) % MwSt.], [#money(tax.amount)])
    },
    table.hline(stroke: 0.6pt + rgb("#999")),
    [*Gesamtsumme*], [*#money(data.totals.gross)*],
  )
]

#v(0.6cm)

// --- Payment / shipping ---
#block(width: 100%)[
  Zahlungsart: #data.payment.method \
  Versandart: #data.shippingMethod \
  \
  Der Rechnungsbetrag ist zahlbar ohne Abzug innerhalb von 14 Tagen. \
  Leistungsdatum entspricht dem Rechnungsdatum, sofern nicht anders angegeben.
  #if data.notes != none and data.notes != "" [
    \
    \
    #text(style: "italic")[#data.notes]
  ]
]
