@php
    $documents = is_array($documents ?? null)
        ? array_values(array_filter($documents, fn ($doc) => is_string($doc) && $doc !== ''))
        : [];
    $editable = (bool) ($editable ?? false);
@endphp

<div class="frc-documents">
    @if(count($documents) > 0)
        <div class="frc-documents__list-head">
            <span class="frc-documents__list-label">Uploaded files</span>
            <span class="frc-documents__count">{{ count($documents) }}</span>
        </div>

        <ul class="frc-documents__list">
            @foreach($documents as $doc)
                @php
                    $ext = frc_storage_extension($doc);
                    $markedForRemoval = $editable && in_array($doc, old('remove_documents', []), true);
                @endphp
                <li class="frc-document-card{{ $markedForRemoval ? ' frc-document-card--marked' : '' }}" data-document-card>
                    @if($editable)
                        <label class="frc-document-card__remove" title="Mark for removal">
                            <input type="checkbox"
                                name="remove_documents[]"
                                value="{{ $doc }}"
                                class="frc-document-card__remove-input"
                                data-document-remove
                                {{ $markedForRemoval ? 'checked' : '' }}>
                            <span class="frc-document-card__remove-ui" aria-hidden="true">
                                <i class="fa-solid fa-trash-can"></i>
                            </span>
                            <span class="visually-hidden">Remove {{ frc_storage_label($doc, $loop->iteration) }}</span>
                        </label>
                    @endif

                    <div class="frc-document-card__icon frc-document-card__icon--{{ $ext !== '' ? $ext : 'file' }}">
                        <i class="fa-solid {{ frc_storage_icon($doc) }}"></i>
                    </div>

                    <div class="frc-document-card__info">
                        <span class="frc-document-card__title">{{ frc_storage_label($doc, $loop->iteration) }}</span>
                        <span class="frc-document-card__meta">{{ frc_storage_meta($doc) }}</span>
                    </div>

                    <a href="{{ frc_storage_url($doc) }}"
                        target="_blank"
                        rel="noopener"
                        class="frc-document-card__view btn-outline-teal">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        <span>View</span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if($editable)
            <p class="frc-documents__hint">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                Tap the trash icon, then save to delete selected files.
            </p>
            @error('remove_documents') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
        @endif
    @elseif(! $editable)
        <p class="frc-documents__empty text-muted mb-0">No documents uploaded yet.</p>
    @endif

    @if($editable)
        <div class="frc-file-upload{{ count($documents) > 0 ? ' frc-file-upload--spaced' : '' }}">
            <input type="file"
                name="documents[]"
                id="childDocumentsInput"
                multiple
                class="frc-file-upload__input @error('documents') is-invalid @enderror @error('documents.*') is-invalid @enderror"
                accept=".pdf,.jpg,.jpeg,.png,.webp"
                data-document-upload-input>
            <label for="childDocumentsInput" class="frc-file-upload__zone" data-document-upload-zone>
                <span class="frc-file-upload__icon" aria-hidden="true">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </span>
                <span class="frc-file-upload__title">Choose files or drop here</span>
                <span class="frc-file-upload__meta">PDF, JPG, PNG, WebP — max 2MB each</span>
            </label>
            <ul class="frc-file-upload__queue" id="childDocumentsQueue" hidden data-document-upload-queue></ul>
            @error('documents') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            @error('documents.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
