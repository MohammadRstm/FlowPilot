import './App.css'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Landing from './Pages/Landing'
import { Copilot } from './Pages/copilot/Copilot'
import CommunityPage from './Pages/community/Community'
import Login from './Pages/Login/Login'
import Signup from './Pages/Signup/Signup'
import ProtectedRoutes from './Pages/components/ProtectedRoutes'
import ProfilePage from './Pages/profile/Profile'
import AboutPage from './Pages/AboutUs'
import SettingsPage from './Pages/Settings/Settings'
import { useBackgroundStreamNotifications } from './Pages/copilot/hooks/ui/useBackgroundStreamNotifications.hook'

function App() {
  // listens for background stream completions globally
  useBackgroundStreamNotifications();

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Landing />} />
        <Route path="/login" element={<Login />} />
        <Route path="/signup" element={<Signup />} />

        <Route path="/copilot" element={
          <ProtectedRoutes>
            <Copilot />
          </ProtectedRoutes>
        }/>
        <Route path="/community" element={
          <ProtectedRoutes>
            <CommunityPage />
          </ProtectedRoutes>
        }/>
        <Route path="/profile/:userId?" element={
          <ProtectedRoutes>
            <ProfilePage />
          </ProtectedRoutes>
        }/>
        <Route path="/settings" element={
          <ProtectedRoutes>
            <SettingsPage />
          </ProtectedRoutes>
        }/>
        <Route path="/aboutus" element={<AboutPage />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
