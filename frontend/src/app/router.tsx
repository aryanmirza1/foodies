import { createBrowserRouter } from 'react-router-dom'
import { AdminLayout } from '../layouts/AdminLayout'
import { CustomerLayout } from '../layouts/CustomerLayout'
import { AdminDashboardPage } from '../features/admin/AdminDashboardPage'
import { AdminLoginPage } from '../features/admin/AdminLoginPage'
import { AdminMenuPage } from '../features/admin/AdminMenuPage'
import { CartPage } from '../features/cart/CartPage'
import { HomePage } from '../features/home/HomePage'
import { FoodDetailsPage } from '../features/menu/FoodDetailsPage'
import { MenuPage } from '../features/menu/MenuPage'
import { OrdersPagePlaceholder } from '../features/orders/OrdersPagePlaceholder'
import { ProfilePagePlaceholder } from '../features/profile/ProfilePagePlaceholder'

export const router = createBrowserRouter([
  {
    element: <CustomerLayout />,
    children: [
      { path: '/', element: <HomePage /> },
      { path: '/menu', element: <MenuPage /> },
      { path: '/menu/:slug', element: <FoodDetailsPage /> },
      { path: '/cart', element: <CartPage /> },
      { path: '/orders', element: <OrdersPagePlaceholder /> },
      { path: '/profile', element: <ProfilePagePlaceholder /> },
    ],
  },
  {
    path: '/admin/login',
    element: <AdminLoginPage />,
  },
  {
    path: '/admin',
    element: <AdminLayout />,
    children: [
      { index: true, element: <AdminDashboardPage /> },
      { path: 'menu', element: <AdminMenuPage /> },
    ],
  },
])
