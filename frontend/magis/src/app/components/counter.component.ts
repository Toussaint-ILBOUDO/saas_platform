import { Component, ElementRef, Input, OnDestroy, OnInit, ViewChild } from '@angular/core';

/**
 * Compteur animé : défile de 0 vers la valeur à l'entrée dans le viewport
 * (requestAnimationFrame). Accepte un suffixe (ex. « + ») et un format français.
 */
@Component({
  selector: 'app-counter',
  styles: [
    `
      :host {
        display: inline-block;
        font-variant-numeric: tabular-nums;
      }
    `,
  ],
  template: `<ng-content></ng-content><span #cible class="counter-valeur">{{ affichage }}</span>`,
})
export class CounterComponent implements OnInit, OnDestroy {
  @Input() fin = 0;
  @Input() suffixe = '';

  @ViewChild('cible', { static: true }) private cible!: ElementRef<HTMLElement>;

  protected affichage = '0';
  private observer?: IntersectionObserver;
  private frame = 0;

  ngOnInit(): void {
    this.observer = new IntersectionObserver((entrees) => {
      for (const entree of entrees) {
        if (entree.isIntersecting) {
          this.compter();
          this.observer?.disconnect();
          break;
        }
      }
    });
    this.observer.observe(this.cible.nativeElement);
  }

  ngOnDestroy(): void {
    this.observer?.disconnect();
    cancelAnimationFrame(this.frame);
  }

  private compter(): void {
    const duree = 1100;
    const debut = performance.now();

    const etape = (maintenant: number) => {
      const progression = Math.min((maintenant - debut) / duree, 1);
      const adouci = 1 - Math.pow(1 - progression, 3);
      this.affichage = Math.round(this.fin * adouci).toLocaleString('fr-FR') + this.suffixe;
      if (progression < 1) {
        this.frame = requestAnimationFrame(etape);
      }
    };
    this.frame = requestAnimationFrame(etape);
  }
}