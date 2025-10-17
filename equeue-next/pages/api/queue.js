export default function handler(req, res) {
  // Mock data for departments and patients
  const departments = [
    { id: 1, name: 'General' },
    { id: 2, name: 'Cardiology' }
  ];

  const patients = [
    { id: 1, queue_number: 1, status: 'waiting', doctor_name: 'Dr. Smith' },
    { id: 2, queue_number: 2, status: 'in-progress', doctor_name: 'Dr. Johnson' }
  ];

  let html = '';

  departments.forEach(dept => {
    html += `<div class='department-queue'>`;
    html += `<h3>${dept.name} Department</h3>`;

    const deptPatients = patients.filter(p => p.id <= 1); // Mock filter

    if (deptPatients.length === 0) {
      html += `<p>No patients in queue.</p>`;
    } else {
      html += `<ul>`;
      deptPatients.forEach(patient => {
        const statusClass = 'status-' + patient.status.replace(' ', '-');
        let patientText = `<span class="queue-number">Queue #${patient.queue_number}</span>`;
        html += `<li class="${statusClass}">${patientText} <span class='status'>[${patient.status.charAt(0).toUpperCase() + patient.status.slice(1)}]</span></li>`;
      });
      html += `</ul>`;
    }
    html += `</div>`;
  });

  res.setHeader('Content-Type', 'text/html');
  res.status(200).send(html);
}
