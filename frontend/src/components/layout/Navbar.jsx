import React, { useState, useRef, useEffect } from 'react';
import { useAuth } from '../../hooks/useAuth';
import { useTheme } from '../../context/ThemeContext';
import { Icon } from '../common/Icon';
import { Link, useNavigate } from 'react-router-dom';

export function Navbar({ onOpenMobileMenu }) {
  const { user, logout } = useAuth();
  const { isDark, toggleTheme } = useTheme();
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const dropdownRef = useRef(null);
  const navigate = useNavigate();

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsDropdownOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [dropdownRef]);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <div className="relative z-10 flex-shrink-0 flex h-16 bg-surface border-b border-border shadow-sm">
      {/* Mobile menu button */}
      <button
        type="button"
        className="px-4 border-r border-border text-text-muted focus:outline-none lg:hidden hover:text-text"
        onClick={onOpenMobileMenu}
      >
        <span className="sr-only">Open sidebar</span>
        <Icon name="menu" className="h-6 w-6" />
      </button>

      <div className="flex-1 px-4 flex justify-between">
        <div className="flex-1 flex">
          {/* Search could go here in the future */}
        </div>
        
        <div className="ml-4 flex items-center md:ml-6 gap-4">
          {/* Theme Toggle */}
          <button
            onClick={toggleTheme}
            className="p-1 rounded-full text-text-muted hover:text-text focus:outline-none transition-colors"
            title="Toggle theme"
          >
            <Icon name={isDark ? 'sun' : 'moon'} className="h-5 w-5" />
          </button>

          {/* Profile dropdown */}
          {user && (
            <div className="ml-3 relative" ref={dropdownRef}>
              <div>
                <button
                  onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                  className="max-w-xs bg-surface rounded-full flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                >
                  <span className="sr-only">Open user menu</span>
                  <div className="w-8 h-8 rounded-full bg-primary-light text-primary flex items-center justify-center font-bold text-sm uppercase">
                    {user.name.charAt(0)}
                  </div>
                </button>
              </div>

              {isDropdownOpen && (
                <div className="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-modal py-1 bg-surface border border-border ring-1 ring-black ring-opacity-5 focus:outline-none">
                  <div className="px-4 py-2 border-b border-border">
                    <p className="text-sm text-text font-medium truncate">{user.name}</p>
                    <p className="text-xs text-text-muted truncate">{user.email}</p>
                  </div>
                  <Link
                    to="/settings"
                    className="block px-4 py-2 text-sm text-text hover:bg-bg"
                    onClick={() => setIsDropdownOpen(false)}
                  >
                    Settings
                  </Link>
                  <button
                    onClick={() => {
                      setIsDropdownOpen(false);
                      handleLogout();
                    }}
                    className="w-full text-left block px-4 py-2 text-sm text-fail hover:bg-fail/10 flex items-center gap-2"
                  >
                    <Icon name="logout" className="h-4 w-4" />
                    Sign out
                  </button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}   