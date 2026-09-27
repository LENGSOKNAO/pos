import { usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export interface Notification {
  id: string;
  company_id: string;
  user_id: string;
  type: string;
  title: string;
  message: string;
  reference_type: string | null;
  reference_id: string | null;
  is_read: boolean;
  created_at: string;
}

export function useNotifications() {
  const { props } = usePage<{ notifications?: Notification[] }>();
  const [notifications, setNotifications] = useState<Notification[]>(props.notifications || []);
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    if (props.notifications) {
      setNotifications(props.notifications);
      setUnreadCount(props.notifications.filter(n => !n.is_read).length);
    }
  }, [props.notifications]);

  const markAsRead = (id: string) => {
    setNotifications(prev =>
      prev.map(n => n.id === id ? { ...n, is_read: true } : n)
    );
    setUnreadCount(prev => Math.max(0, prev - 1));
  };

  const markAllAsRead = () => {
    setNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
    setUnreadCount(0);
  };

  const addNotification = (notification: Notification) => {
    setNotifications(prev => [notification, ...prev]);
    if (!notification.is_read) {
      setUnreadCount(prev => prev + 1);
    }
  };

  const removeNotification = (id: string) => {
    setNotifications(prev => {
      const notification = prev.find(n => n.id === id);
      if (notification && !notification.is_read) {
        setUnreadCount(c => Math.max(0, c - 1));
      }
      return prev.filter(n => n.id !== id);
    });
  };

  return {
    notifications,
    unreadCount,
    markAsRead,
    markAllAsRead,
    addNotification,
    removeNotification,
  };
}