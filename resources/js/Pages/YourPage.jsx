import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";

export default function YourPage({ auth }) {
  return (
    <AuthenticatedLayout
      user={auth.user}
      header={{
        title: "Your Dynamic Title",
        description: "This is a dynamic description for this page",
        backUrl: route("dashboard"), // Optional back button URL
      }}
    >
      <Head title="Your Page" />

      <div className="py-12">
        {/* Your page content */}
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div className="p-6 text-gray-900">Content goes here</div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
