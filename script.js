async function uploadFile() {
    const errorMessage = document.getElementById('errorMessage'); // Get the error message element
    errorMessage.textContent = ""; // Clear any previous error message

    const uploadType = document.querySelector('input[name="uploadType"]:checked').value;
    const fileInput = document.getElementById('pgnFile');
    const textInput = document.getElementById('pgnText');
    //const statusElement = document.getElementById('status');

    let pgnContent = null;

    // Determine the input type
    if (uploadType === 'file') {
        const file = fileInput.files[0];

        if (!file) {
            errorMessage.textContent = "Please select a file to upload.";
            return;
        }

        // Wait for the file to be read
        pgnContent = await readFileAsText(file);
    } else if (uploadType === 'text') {
        pgnContent = textInput.value.trim();

        if (!pgnContent) {
            errorMessage.textContent = "Please paste your PGN content.";
            return;
        }
    }

    // Send the PGN content to the server
    await sendPGNToServer(pgnContent, errorMessage);
}

// Helper function to read a file as text
function readFileAsText(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (event) => resolve(event.target.result);
        reader.onerror = (error) => reject(error);
        reader.readAsText(file);
    });
}

// Helper function to send PGN content to the server
async function sendPGNToServer(pgnContent, errorMessage) {
    const formData = new FormData();
    formData.append('pgnContent', pgnContent);

    try {
        const response = await fetch('upload.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.text();
        errorMessage.textContent = result;
        errorMessage.style.color = response.ok ? 'green' : 'red';
    } catch (error) {
        errorMessage.textContent = 'Error uploading the PGN.';
        errorMessage.style.color = 'red';
        console.error(error);
    }
}

