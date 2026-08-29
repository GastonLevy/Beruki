import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { Subject, of, throwError } from 'rxjs';
import { Auth } from '../../../features/auth/services/auth';

import { Navbar } from './navbar';

describe('Navbar', () => {
  let component: Navbar;
  let fixture: ComponentFixture<Navbar>;
  let auth: {
    isAdmin: ReturnType<typeof vi.fn>;
    createAdminSession: ReturnType<typeof vi.fn>;
    logoutAdminSession: ReturnType<typeof vi.fn>;
    logout: ReturnType<typeof vi.fn>;
  };

  beforeEach(async () => {
    auth = {
      isAdmin: vi.fn().mockReturnValue(true),
      createAdminSession: vi.fn().mockReturnValue(of({ status: 'ok' })),
      logoutAdminSession: vi.fn().mockReturnValue(of({ status: 'ok' })),
      logout: vi.fn(),
    };

    await TestBed.configureTestingModule({
      imports: [Navbar],
      providers: [
        provideRouter([]),
        {
          provide: Auth,
          useValue: auth,
        },
      ],
    }).compileComponents();
  });

  function createComponent(): void {
    fixture = TestBed.createComponent(Navbar);
    component = fixture.componentInstance;
    fixture.detectChanges();
  }

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('should create', () => {
    createComponent();

    expect(component).toBeTruthy();
  });

  it('calls the admin session bridge before navigating to admin', () => {
    const bridgeResponse$ = new Subject<{ status: string }>();
    auth.createAdminSession.mockReturnValue(bridgeResponse$);
    createComponent();
    const navigateToAdmin = vi.spyOn(component as any, 'navigateToAdmin').mockImplementation(() => undefined);

    component.goToAdmin();

    expect(auth.createAdminSession).toHaveBeenCalledOnce();
    expect(navigateToAdmin).not.toHaveBeenCalled();

    bridgeResponse$.next({ status: 'ok' });

    expect(navigateToAdmin).toHaveBeenCalledOnce();
  });

  it('does not navigate to admin when the bridge fails', () => {
    auth.createAdminSession.mockReturnValue(throwError(() => new Error('forbidden')));
    createComponent();
    const navigateToAdmin = vi.spyOn(component as any, 'navigateToAdmin').mockImplementation(() => undefined);

    component.goToAdmin();

    expect(auth.createAdminSession).toHaveBeenCalledOnce();
    expect(navigateToAdmin).not.toHaveBeenCalled();
    expect(component.isOpeningAdmin).toBe(false);
  });

  it('hides the admin access for non-admin users', () => {
    auth.isAdmin.mockReturnValue(false);
    createComponent();

    expect(fixture.nativeElement.textContent).not.toContain('Panel de Administr');
  });

  it('logs out the admin session before clearing the local token', () => {
    createComponent();
    const router = TestBed.inject(Router);
    const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

    component.logout();

    expect(auth.logoutAdminSession).toHaveBeenCalledOnce();
    expect(auth.logout).toHaveBeenCalledOnce();
    expect(navigate).toHaveBeenCalledWith(['/login']);
  });
});
