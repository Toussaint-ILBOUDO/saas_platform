import { Injectable, inject } from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { Meta, Title } from '@angular/platform-browser';

/**
 * SEO (P4) : titre + description + canonical par page.
 */
@Injectable({ providedIn: 'root' })
export class SeoService {
  private readonly title = inject(Title);
  private readonly meta = inject(Meta);
  private readonly doc = inject(DOCUMENT);

  definir(titre: string, description?: string): void {
    this.title.setTitle(titre);
    if (description) {
      this.meta.updateTag({ name: 'description', content: description });
    }

    const url = this.doc.defaultView?.location.href;
    if (url) {
      let lien = this.doc.head.querySelector<HTMLLinkElement>('link[rel=canonical]');
      if (!lien) {
        lien = this.doc.createElement('link');
        lien.rel = 'canonical';
        this.doc.head.appendChild(lien);
      }
      lien.href = url.split('#')[0];
    }
  }
}