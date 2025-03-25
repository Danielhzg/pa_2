// ...existing code...

import Header from "@/Components/Header";

export default function Authenticated({ user, header, children }) {
  const [showingNavigationDropdown, setShowingNavigationDropdown] =
    useState(false);

  return (
    <div className="min-h-screen bg-gray-100">
      <Header
        user={user}
        title={header?.title || "Dashboard"}
        description={header?.description}
        backUrl={header?.backUrl}
      />

      {/* Adding padding to the top to prevent content from being hidden under the fixed header */}
      <div className="pt-24">
        <main>{children}</main>
      </div>
    </div>
  );
}
// ...existing code...
